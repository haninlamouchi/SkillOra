<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FormationController extends AbstractController
{
    private $formations = [
        1 => ['id' => 1, 'title' => 'Symfony Basics', 'description' => 'Learn Symfony step by step.'],
        2 => ['id' => 2, 'title' => 'PHP Advanced', 'description' => 'Deep dive into PHP.'],
    ];

    #[Route('/formation', name: 'app_formation')]
    public function index(): Response
    {
        return $this->render('backoffice/formation/index.html.twig', [
            'controller_name' => 'FormationController', 'formations' => $this->formations,
        ]); 
    } 
}