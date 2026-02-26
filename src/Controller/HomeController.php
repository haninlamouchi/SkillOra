<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * Route /home - Redirige vers la page d'accueil avec l'userId si connecté
     * Cette route existe pour compatibilité mais redirige simplement
     */
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        if ($this->getUser()) {
            // ✅ Si admin → redirection directe vers le backoffice
            if ($this->getUser()->getRole() === 'admin') {
                return $this->redirectToRoute('app_admin_dashboard');
            }

            // Sinon → page d'accueil normale avec userId
            return $this->redirectToRoute('front_home_user', ['userId' => $this->getUser()->getId()]);
        }
        
        // Si non connecté → page d'accueil publique
        return $this->redirectToRoute('front_home');
    }
}