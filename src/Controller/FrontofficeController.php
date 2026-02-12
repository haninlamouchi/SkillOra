<?php

namespace App\Controller;

use App\Repository\UserRepository;
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

    #[Route('/home/{userId}', name: 'front_home_user', requirements: ['userId' => '\d+'])]
    public function homeUser(int $userId, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        return $this->render('frontoffice/home.html.twig', [
            'currentUser' => $user,
        ]);
    }
}