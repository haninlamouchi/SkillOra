<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/etudiant')]
#[IsGranted('ROLE_ETUDIANT')]
class EtudiantController extends AbstractController
{
    #[Route('/dashboard', name: 'app_etudiant_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('etudiant/dashboard.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}