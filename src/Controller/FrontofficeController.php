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
}
