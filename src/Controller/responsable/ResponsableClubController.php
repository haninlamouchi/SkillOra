<?php

namespace App\Controller\responsable;

use App\Entity\Club;
use App\Entity\DemandeMembre;
use App\Enum\StatutDemande;
use App\Form\ClubType;
use App\Repository\ClubRepository;
use App\Repository\DemandeMembreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\SmsService;


#[Route('/responsable/club')]
class ResponsableClubController extends AbstractController
{
        // Voir le détail d'un club
    #[Route('/show', name: 'club_show', methods: ['GET'])]
    public function show(ClubRepository $clubRepository): Response
    {
        $user = $this->getUser();

        $club = $clubRepository->findOneBy(['responsable' => $user]);

        if (!$club) {
            $this->addFlash('danger', 'Vous n\'êtes pas responsable de ce club.');
            return $this->redirectToRoute('responsable_adhesion_index');
        }

        return $this->render('frontoffice/responsable/showResp.html.twig', [
            'club' => $club,
        ]);
    }

#[Route('/edit/{id}', name: 'club_edit', methods: ['GET', 'POST'])]
public function edit(
    Club $club,
    Request $request,
    EntityManagerInterface $entityManager
): Response {

    // ✅ sécurité : le responsable modifie seulement SON club
    if ($club->getResponsable() !== $this->getUser()) {
        throw $this->createAccessDeniedException('Accès refusé.');
    }

    $form = $this->createForm(ClubType::class, $club, [
        'disable_responsable' => true,
    ]);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // Doctrine détecte automatiquement les changements
        $entityManager->flush();

        $this->addFlash('success', 'Club mis à jour avec succès.');

        return $this->redirectToRoute('club_show');
    }

    return $this->render('frontoffice/responsable/editResp.html.twig', [
        'club' => $club,
        'form' => $form,
    ]);
}
    // Liste des demandes d'adhésion de son club uniquement
    #[Route('/adhesions', name: 'responsable_adhesion_index', methods: ['GET'])]
    public function adhesions(
        DemandeMembreRepository $repo,
        ClubRepository $clubRepository
    ): Response {
        $user = $this->getUser();

        $club = $clubRepository->findOneBy(['responsable' => $user]);

        if (!$club) {
            $this->addFlash('danger', 'Vous n\'êtes responsable d\'aucun club.');
            return $this->redirectToRoute('user_club_index');
        }

        $demandes = $repo->findBy([
            'club'   => $club,
            'statut' => StatutDemande::en_attente,
        ]);

        return $this->render('frontoffice/responsable/indexAdhesion.html.twig', [
            'demandes' => $demandes,
            'club'     => $club,
        ]);
    }

    // Accepter une demande d'adhésion
    #[Route('/adhesion/{id}/accepter', name: 'responsable_adhesion_accepter', methods: ['POST'])]
    public function accepterAdhesion(
        DemandeMembre $demande,
        EntityManagerInterface $entityManager,
        SmsService $smsService
    ): Response {
        $responsable = $this->getUser();

        // sécurité : vérifier responsable du club
        if ($demande->getClub()->getResponsable() !== $responsable) {
            $this->addFlash('danger', 'Vous n\'êtes pas responsable de ce club.');
            return $this->redirectToRoute('responsable_adhesion_index');
        }

        // accepter la demande
        $demande->setStatut(StatutDemande::accepte);

        // récupérer l'utilisateur demandeur
        $user = $demande->getUser();

        // changer son rôle dans la table User
        $user->setRole('membre');

        // sauvegarde
        $entityManager->flush();

        // ✅ SMS au membre accepté
        if ($user->getTelephone()) {
            $smsService->send(
                $user->getTelephone(),
                '🎉 Félicitations ' . $user->getNom() . ' ! ' .
                'Votre demande d\'adhésion au club ' .
                $demande->getClub()->getNom() .
                ' a été acceptée. Bienvenue !'
            );
        }

        $this->addFlash('success', 'Adhésion acceptée. L’utilisateur est maintenant membre.');

        return $this->redirectToRoute('responsable_adhesion_index');
    }

    // Refuser une demande d'adhésion
    #[Route('/adhesion/{id}/refuser', name: 'responsable_adhesion_refuser', methods: ['POST'])]
    public function refuserAdhesion(
        DemandeMembre $demande,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if ($demande->getClub()->getResponsable() !== $user) {
            $this->addFlash('danger', 'Vous n\'êtes pas responsable de ce club.');
            return $this->redirectToRoute('responsable_adhesion_index');
        }

        $entityManager->remove($demande);
        $entityManager->flush();

        $this->addFlash('warning', 'Adhésion refusée.');
        return $this->redirectToRoute('responsable_adhesion_index');
    }
}