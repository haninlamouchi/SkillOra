<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $user = $this->getUser();
        
        // Rediriger selon le rôle de l'utilisateur
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('app_admin_dashboard');
        }
        
        if (in_array('ROLE_RESPONSABLE_CLUB', $user->getRoles())) {
            return $this->redirectToRoute('app_responsable_dashboard');
        }
        
        if (in_array('ROLE_ETUDIANT', $user->getRoles())) {
            return $this->redirectToRoute('app_etudiant_dashboard');
        }
        
        throw $this->createAccessDeniedException('Rôle non reconnu');
    }
}