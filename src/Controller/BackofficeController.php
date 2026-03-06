<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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

    #[Route('backoffice/user/{id}', name: 'app_user_show_backoffice')]
    public function show(User $user): Response
    {
        return $this->render('backoffice/user/show.html.twig', [
            'user' => $user,
        ]);
    }
   #[Route('/backoffice/user/{id}/edit', name: 'app_user_edit_backoffice')]
    public function edit(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $oldPassword = $user->getPassword();

        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true,
        ]);

        // Pré-remplis dateInscription
        $form->get('dateInscription')->setData($user->getDateInscription());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword && is_string($plainPassword)) {
                $user->setPassword($hasher->hashPassword($user, $plainPassword));
            } else {
                $user->setPassword($oldPassword ?? '');
            }
            $em->flush();
            $this->addFlash('success', '✅ Profile updated successfully!');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        // ← Affiche les erreurs dans le terminal Symfony
        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                if ($error instanceof \Symfony\Component\Form\FormError) {
                    $origin = $error->getOrigin();
                    $fieldName = $origin ? $origin->getName() : 'unknown';
                    dump('ERREUR: ' . $error->getMessage() . ' | Champ: ' . $fieldName);
                }
            }
        }

        return $this->render('backoffice/user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

}