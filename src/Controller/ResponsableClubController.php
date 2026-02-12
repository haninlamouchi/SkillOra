<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/responsable')]
#[IsGranted('ROLE_RESPONSABLE_CLUB')]
class ResponsableClubController extends AbstractController
{
    #[Route('/dashboard', name: 'app_responsable_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('responsable/dashboard.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}