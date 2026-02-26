<?php

namespace App\Controller;

use App\Entity\Club;
use App\Entity\DemandeAdhesion;
use App\Enum\StatutDemande;
use App\Repository\DemandeAdhesionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/club')]
class FrontClubController extends AbstractController
{
    /**
     * Soumettre une demande d'adhésion à un club.
     */
    #[Route('/{id}/rejoindre', name: 'front_club_rejoindre', methods: ['POST'])]
    public function rejoindre(
        Club $club,
        Request $request,
        EntityManagerInterface $entityManager,
        DemandeAdhesionRepository $demandeRepo
    ): Response {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('rejoindre' . $club->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token invalide.');
            return $this->redirectToRoute('front_home');
        }

        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si une demande existe déjà
        $existing = $demandeRepo->findOneBy([
            'user'  => $user,
            'club'  => $club,
        ]);

        if ($existing) {
            $this->addFlash('warning', 'Vous avez déjà soumis une demande pour ce club.');
            return $this->redirectToRoute('front_home');
        }

        // Créer la demande d'adhésion
        $demande = new DemandeAdhesion();
        $demande->setUser($user);
        $demande->setClub($club);
        $demande->setStatut(StatutDemande::en_attente);

        $entityManager->persist($demande);
        $entityManager->flush();

        $this->addFlash('success', 'Votre demande d\'adhésion au club « ' . $club->getNom() . ' » a bien été envoyée !');

        return $this->redirectToRoute('front_home');
    }
}