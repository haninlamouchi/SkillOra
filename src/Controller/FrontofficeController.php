<?php

namespace App\Controller;

use App\Repository\ClubRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FrontofficeController extends AbstractController
{
    /**
     * Page d'accueil publique — affichée aux visiteurs non connectés.
     */
    #[Route('/', name: 'front_home')]
    public function index(ClubRepository $clubRepository): Response
    {
        return $this->render('frontoffice/home.html.twig', [
            'clubs' => $clubRepository->findAll(),
        ]);
    }

    /**
     * Page d'accueil pour un utilisateur connecté.
     */
    #[Route('/home/{userId}', name: 'front_home_user', requirements: ['userId' => '\d+'])]
    public function homeUser(int $userId, UserRepository $userRepository, ClubRepository $clubRepository): Response
    {
        $user = $userRepository->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        return $this->render('frontoffice/home.html.twig', [
            'currentUser' => $user,
            'clubs'       => $clubRepository->findAll(),
        ]);
    }
}