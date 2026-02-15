<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FrontofficeController extends AbstractController
{
    #[Route('/', name: 'front_home')]
    public function index(): Response
    {
        return $this->render('frontoffice/home.html.twig');
    }

    #[Route('/responsable/dashboard', name: 'responsable_dashboard')]
    public function responsableDashboard(): Response
    {
        return $this->render('frontoffice/responsable/dashboard.html.twig');
    }

    #[Route('/etudiant/dashboard', name: 'etudiant_dashboard')]
    public function etudiantDashboard(): Response
    {
        return $this->render('frontoffice/etudiant/dashboard.html.twig');
    }
}