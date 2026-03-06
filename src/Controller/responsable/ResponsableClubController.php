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
    // ──────────────────────────────────────────────
    // CLUB
    // ──────────────────────────────────────────────

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

    #[Route('/edit', name: 'club_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ClubRepository $clubRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $club = $clubRepository->findOneBy(['responsable' => $user]);

        if (!$club) {
            $this->addFlash('danger', 'Vous n\'êtes pas responsable d\'aucun club.');
            return $this->redirectToRoute('responsable_adhesion_index');
        }

        $form = $this->createForm(ClubType::class, $club, [
            'disable_responsable' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $club->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Club mis à jour avec succès.');
            return $this->redirectToRoute('club_show');
        }

        return $this->render('frontoffice/responsable/editResp.html.twig', [
            'club' => $club,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/members', name: 'club_members', methods: ['GET'])]
    public function members(Club $club): Response
    {
        $user = $this->getUser();

        if ($club->getResponsable() !== $user) {
            $this->addFlash('danger', 'Vous n\'êtes pas responsable de ce club.');
            return $this->redirectToRoute('responsable_adhesion_index');
        }

        return $this->render('frontoffice/responsable/members.html.twig', [
            'club'    => $club,
            'membres' => $club->getMembres(),
        ]);
    }

    // ──────────────────────────────────────────────
    // DEMANDES MEMBRE (DemandeMembre)
    // ──────────────────────────────────────────────

    #[Route('/adhesions', name: 'responsable_adhesion_index', methods: ['GET'])]
    public function adhesions(
        DemandeMembreRepository $repo,
        \App\Repository\DemandeAdhesionRepository $adhesionRepo,
        ClubRepository $clubRepository
    ): Response {
        $user = $this->getUser();
        $club = $clubRepository->findOneBy(['responsable' => $user]);

        if (!$club) {
            $this->addFlash('danger', 'Vous n\'êtes responsable d\'aucun club.');
            return $this->redirectToRoute('user_club_index');
        }

        $demandesMembre = $repo->findBy([
            'club'   => $club,
            'statut' => StatutDemande::en_attente,
        ]);

        $demandesAdhesion = $adhesionRepo->findBy([
            'club'   => $club,
            'statut' => StatutDemande::en_attente,
        ]);

        return $this->render('frontoffice/responsable/indexAdhesion.html.twig', [
            'demandesMembre'   => $demandesMembre,
            'demandesAdhesion' => $demandesAdhesion,
            'club'             => $club,
        ]);
    }

    #[Route('/adhesion/{id}/accepter', name: 'responsable_adhesion_accepter', methods: ['POST'])]
    public function accepterAdhesion(
        DemandeMembre $demande,
        EntityManagerInterface $entityManager,
        SmsService $smsService
    ): Response {
        $responsable = $this->getUser();
        $club = $demande->getClub();
        
        if (!$club || $club->getResponsable() !== $responsable) {
            $this->addFlash('danger', 'Vous n\'êtes pas responsable de ce club.');
            return $this->redirectToRoute('responsable_adhesion_index');
        }

        $demande->setStatut(StatutDemande::accepte);

        $userToAdd = $demande->getUser();

        // Ajout au club + changement de rôle (logique contrôleur 1)
        if ($club && $userToAdd) {
            $club->addMembre($userToAdd);
        }
        if ($userToAdd) {
            $userToAdd->setRole('membre');
            $entityManager->persist($userToAdd);
        }
        $entityManager->persist($club);
        $entityManager->persist($demande);
        $entityManager->flush();

        // Envoi SMS (logique contrôleur 2)
        if ($userToAdd && $userToAdd->getTelephone() && $club) {
            $smsService->send(
                $userToAdd->getTelephone(),
                '🎉 Félicitations ' . $userToAdd->getNom() . ' ! ' .
                'Votre demande d\'adhésion au club ' .
                $club->getNom() .
                ' a été acceptée. Bienvenue !'
            );
        }

        $this->addFlash('success', 'Adhésion acceptée. L\'utilisateur est maintenant membre.');
        return $this->redirectToRoute('responsable_adhesion_index');
    }

    #[Route('/adhesion/{id}/refuser', name: 'responsable_adhesion_refuser', methods: ['POST'])]
    public function refuserAdhesion(
        DemandeMembre $demande,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        $club = $demande->getClub();
        if (!$club || $club->getResponsable() !== $user) {
            $this->addFlash('danger', 'Vous n\'êtes pas responsable de ce club.');
            return $this->redirectToRoute('responsable_adhesion_index');
        }

        $entityManager->remove($demande);
        $entityManager->flush();

        $this->addFlash('warning', 'Adhésion refusée.');
        return $this->redirectToRoute('responsable_adhesion_index');
    }

    // ──────────────────────────────────────────────
    // DEMANDES ADHESION (DemandeAdhesion)
    // ──────────────────────────────────────────────

    #[Route('/adhesion-adhesion/{id}/accepter', name: 'responsable_adhesion_adhesion_accepter', methods: ['POST'])]
    public function accepterAdhesionAdhesion(
        \App\Entity\DemandeAdhesion $demande,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $club = $demande->getClub();

        if (!$club || $club->getResponsable() !== $user) {
            $this->addFlash('danger', 'Vous n\'êtes pas responsable de ce club.');
            return $this->redirectToRoute('responsable_adhesion_index');
        }

        $demande->setStatut(StatutDemande::accepte);

        $userToAdd = $demande->getUser();

        if ($club && $userToAdd) {
            $club->addMembre($userToAdd);
        }
        if ($userToAdd) {
            $userToAdd->setRole('membre');
            $entityManager->persist($userToAdd);
        }
        $entityManager->persist($club);
        $entityManager->persist($demande);
        $entityManager->flush();

        $this->addFlash('success', 'Adhésion acceptée.');
        return $this->redirectToRoute('responsable_adhesion_index');
    }

    #[Route('/adhesion-adhesion/{id}/refuser', name: 'responsable_adhesion_adhesion_refuser', methods: ['POST'])]
    public function refuserAdhesionAdhesion(
        \App\Entity\DemandeAdhesion $demande,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        $club = $demande->getClub();
        if (!$club || $club->getResponsable() !== $user) {
            $this->addFlash('danger', 'Vous n\'êtes pas responsable de ce club.');
            return $this->redirectToRoute('responsable_adhesion_index');
        }

        $entityManager->remove($demande);
        $entityManager->flush();

        $this->addFlash('warning', 'Adhésion refusée.');
        return $this->redirectToRoute('responsable_adhesion_index');
    }
}