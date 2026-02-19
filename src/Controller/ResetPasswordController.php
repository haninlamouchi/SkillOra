<?php
// src/Controller/ResetPasswordController.php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResetPasswordController extends AbstractController
{
    // ─── ÉTAPE 1 : Formulaire "Entrez votre email" ───────────────────────────
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        $error = null;
        $success = false;

        if ($request->isMethod('POST')) {
            $emailInput = $request->request->get('email');
            $user = $userRepository->findOneBy(['email' => $emailInput]);

            if ($user) {
                // Générer un token sécurisé
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(new \DateTime('+1 hour'));
                $em->flush();

                // Construire le lien de reset
                $resetLink = $this->generateUrl(
                    'app_reset_password',
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                // Envoyer l'email
            try {
                $email = (new Email())
                    ->from('malekfathidabbek@gmail.com')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe - Skillora')
                    ->html($this->renderView('security/reset_password_email.html.twig', [
                        'resetLink' => $resetLink,
                        'user' => $user,
                        'expiresAt' => $user->getResetTokenExpiresAt(),
                    ]));

                $mailer->send($email);

            } catch (\Exception $e) {
            }
            }

            // On affiche toujours un succès (sécurité : ne pas révéler si l'email existe)
            $success = true;
        }

        return $this->render('security/forgot_password.html.twig', [
            'error' => $error,
            'success' => $success,
        ]);
    }

    // ─── ÉTAPE 2 : Formulaire "Nouveau mot de passe" ─────────────────────────
    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $userRepository->findOneBy(['resetToken' => $token]);

        // Vérifier que le token est valide et non expiré
        if (!$user || $user->getResetTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('danger', 'Ce lien est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $confirm  = $request->request->get('confirm_password');

            if (strlen($password) < 8) {
                $error = 'Le mot de passe doit contenir au moins 8 caractères.';
            } elseif ($password !== $confirm) {
                $error = 'Les mots de passe ne correspondent pas.';
            } else {
                // Hasher et sauvegarder le nouveau mot de passe
                $hashed = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashed);

                // Invalider le token
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);
                $em->flush();

                $this->addFlash('success', 'Mot de passe réinitialisé avec succès !');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
            'error' => $error,
        ]);
    }
}