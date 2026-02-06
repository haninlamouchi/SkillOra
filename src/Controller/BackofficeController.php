<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BackofficeController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('backoffice/dashboard.html.twig');
    }

    #[Route('/admin/index', name: 'admin_index')]
    public function index(): Response
    {
        return $this->render('backoffice/index.html.twig');
    }

}