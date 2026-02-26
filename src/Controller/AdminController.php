<?php

namespace App\Controller;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
    $users = $userRepository->findBy(['role' => $role]);

    return $this->render('backoffice/user/users_by_role.html.twig', [
        'users' => $users,
        'role'  => $role,
    ]);
}  
}
