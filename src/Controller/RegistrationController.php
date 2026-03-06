<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Service\EmailValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\NotificationServiceUser;


class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        EmailValidationService $emailValidator, 
        NotificationServiceUser $notificationServiceUser  // ← AJOUTÉ
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ===== VALIDATION EMAIL DISIFY =====
            $emailCheck = $emailValidator->validate($user->getEmail() ?? '');

            if (!$emailCheck['isValid']) {
                $this->addFlash('error', '❌ ' . $emailCheck['message']);
                return $this->render('registration/register.html.twig', ['form' => $form]);
            }

            if ($emailCheck['isDisposable']) {
                $this->addFlash('error', '❌ ' . $emailCheck['message']);
                return $this->render('registration/register.html.twig', ['form' => $form]);
            }

            if (!$emailCheck['isDns']) {
                $this->addFlash('error', '❌ ' . $emailCheck['message']);
                return $this->render('registration/register.html.twig', ['form' => $form]);
            }
            // ===== FIN VALIDATION =====

            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('password')->getData()
            );
            $user->setPassword($hashedPassword);
            $user->setRole('etudiant');

            try {
                $entityManager->persist($user);
                $entityManager->flush();
                $notificationServiceUser->notifyNewUser($user);
                return $this->redirectToRoute('front_home', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', '❌ Erreur : ' . $e->getMessage());
            }
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form,
        ]);
    }
    #[Route('/api/validate-email', name: 'api_validate_email', methods: ['GET'])]
    public function validateEmail(
        Request $request,
        EmailValidationService $emailValidator
    ): JsonResponse {
        $email = $request->query->get('email', '');
        
        if (empty($email)) {
            return new JsonResponse(['isValid' => false, 'message' => 'Email is required']);
        }
        
        return new JsonResponse($emailValidator->validate($email));
    }
}
