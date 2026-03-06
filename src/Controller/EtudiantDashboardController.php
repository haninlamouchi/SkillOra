<?php

namespace App\Controller;

use App\Entity\DemandeAdhesion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/etudiant-dashboard')]
final class EtudiantDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_etudiant_dashboard_student')]
    public function dashboard(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        // Récupérer les demandes d'adhésion
        $demandesAdhesion = $entityManager->getRepository(DemandeAdhesion::class)
            ->findBy(['user' => $user]);
        // Clubs acceptés
        $clubsAcceptes = [];
        foreach ($demandesAdhesion as $demande) {
            if ($demande->getStatut() === \App\Enum\StatutDemande::accepte) {
                $clubsAcceptes[] = $demande->getClub();
            }
        }
        return $this->render('frontoffice/etudiant/dashboard_etudiant.html.twig', [
            'user' => $user,
            'demandesAdhesion' => $demandesAdhesion,
            'clubsAcceptes' => $clubsAcceptes,
        ]);
    }
}
