<?php

namespace App\Controller;

use App\Entity\DemandeAdhesion;
use App\Enum\StatutDemande;
use App\Repository\ClubRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/demande-adhesion')]
class DemandeAdhesionController extends AbstractController
{
    #[Route('/new/{clubId}', name: 'app_demande_adhesion_new', requirements: ['clubId' => '\d+'])]
    public function new(
        int $clubId,
        Request $request,
        ClubRepository $clubRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException('Utilisateur invalide.');
        }
        
        $club = $clubRepository->find($clubId);

        if (!$club) {
            throw $this->createNotFoundException('Club introuvable.');
        }

        // ✅ Use the Enum, not a raw string
        $existingDemande = $entityManager->getRepository(DemandeAdhesion::class)
            ->findOneBy([
                'user'   => $user,
                'club'   => $club,
                'statut' => StatutDemande::en_attente,  // ← was: 'en_attente'
            ]);

        if ($existingDemande) {
            $this->addFlash('warning', 'Vous avez déjà une demande en attente pour ce club.');
            return $this->redirectToRoute('app_etudiant_dashboard_student');
        }

        // ✅ Also check if already accepted — no point re-applying
        $acceptedDemande = $entityManager->getRepository(DemandeAdhesion::class)
            ->findOneBy([
                'user'   => $user,
                'club'   => $club,
                'statut' => StatutDemande::accepte,
            ]);

        if ($acceptedDemande) {
            $this->addFlash('info', 'Vous êtes déjà membre de ce club.');
            return $this->redirectToRoute('app_etudiant_dashboard_student');
        }

        $demande = new DemandeAdhesion();
        $demande->setUser($user);
        $demande->setClub($club);
        $demande->setStatut(StatutDemande::en_attente); // ✅ Set explicitly
        $demande->setDateInscription(new \DateTime());
        $entityManager->persist($demande);
        $entityManager->flush();

        $this->addFlash('success', "Votre demande d'adhésion a été envoyée au responsable du club.");
        return $this->redirectToRoute('app_etudiant_dashboard_student');
    }
}