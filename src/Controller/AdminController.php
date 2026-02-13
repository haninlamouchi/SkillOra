<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('backoffice/dashboard.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/users/role/{role}', name: 'app_admin_users_by_role')]
    public function usersByRole(string $role, UserRepository $userRepository): Response
    {
        // Valider que le rôle est correct
        if (!in_array($role, ['responsable_club', 'etudiant', 'admin'])) {
            throw $this->createNotFoundException('Invalid role');
        }

        // Récupérer les utilisateurs par rôle
        $users = $userRepository->findBy(['role' => $role]);

        // ✅ CORRECTION ICI : Pointer vers backoffice/users_list.html.twig
        return $this->render('backoffice/users_list.html.twig', [
            'users' => $users,
            'role' => $role,
            'user' => $this->getUser(),
        ]);
    }
}