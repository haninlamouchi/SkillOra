<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('backoffice/user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
            if (!$plainPassword) {
                $this->addFlash('error', 'Le mot de passe est obligatoire');
                return $this->render('user/new.html.twig', ['user' => $user, 'form' => $form]);
            }
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            // Vich gere l'upload automatiquement
            try {
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', 'Utilisateur cree avec succes !');
                return $this->redirectToRoute('front_home', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur : ' . $e->getMessage());
            }
        }
        return $this->render('user/new.html.twig', ['user' => $user, 'form' => $form]);
    }

    #[Route('/user/{id<\d+>}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user'        => $user,
            'currentUser' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(
    Request $request,
    User $user,
    EntityManagerInterface $entityManager,
    UserPasswordHasherInterface $passwordHasher
): Response {
    $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
    $plainPassword = $form->get('password')->getData();
    if ($plainPassword) {
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
    }
    $entityManager->flush();

    $this->addFlash('success', 'Profile updated! Please log in again.');
    
    // ✅ Redirige vers logout qui déconnecte puis va vers front_home
    return $this->redirectToRoute('app_logout');
}

    return $this->render('user/edit.html.twig', [
        'user'        => $user,
        'form'        => $form,
        'currentUser' => $user,
    ]);
}

    // Route separee /delete pour eviter conflit avec show /{id} GET
    #[Route('/{id}/delete', name: 'app_user_delete', methods: ['POST'])]
    public function delete(
    Request $request, 
    User $user, 
    EntityManagerInterface $entityManager,
    TokenStorageInterface $tokenStorage  // ← ajouter
): Response {
    if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
        
        // ✅ Déconnecter avant de supprimer
        $tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Account deleted successfully.');
    } else {
        $this->addFlash('error', 'Invalid CSRF token.');
    }

    return $this->redirectToRoute('front_home', [], Response::HTTP_SEE_OTHER);
}
    
}