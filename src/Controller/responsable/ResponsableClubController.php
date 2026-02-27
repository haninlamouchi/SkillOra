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

#[Route('/responsable/club')]
class ResponsableClubController extends AbstractController
{
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
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if ($demande->getClub()->getResponsable() !== $user) {
            $this->addFlash('danger', 'Vous n\'êtes pas responsable de ce club.');
            return $this->redirectToRoute('responsable_adhesion_index');
        }

        $demande->setStatut(StatutDemande::accepte);
        $entityManager->flush();

        $this->addFlash('success', 'Adhésion acceptée.');
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
    
    // Voir le détail d'un club
    #[Route('/{id}', name: 'club_show', methods: ['GET'])]
    public function show(Club $club): Response
    {
        $user = $this->getUser();

        if ($club->getResponsable() !== $user) {
            $this->addFlash('danger', 'Vous n\'êtes pas responsable de ce club.');
            return $this->redirectToRoute('responsable_adhesion_index');
        }

        return $this->render('frontoffice/responsable/showResp.html.twig', [
            'club' => $club,
        ]);
    }

    // Modifier un club
    #[Route('/{id}/edit', name: 'club_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Club $club, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($club->getResponsable() !== $user) {
            $this->addFlash('danger', 'Vous n\'êtes pas responsable de ce club.');
            return $this->redirectToRoute('responsable_adhesion_index');
        }

        $form = $this->createForm(ClubType::class, $club, [
            'disable_responsable' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Club mis à jour avec succès.');
            return $this->redirectToRoute('club_show', ['id' => $club->getId()]);
        }

        return $this->render('frontoffice/responsable/editResp.html.twig', [
            'club' => $club,
            'form' => $form,
        ]);
    }
}