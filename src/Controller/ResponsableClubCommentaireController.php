<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Form\CommentaireType;
use App\Repository\CommentaireRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/responsable-club/commentaire')]
class ResponsableClubCommentaireController extends AbstractController
{
    // ✅ PAGE DE SUPERVISION : TOUS LES COMMENTAIRES
    #[Route('/{userId}', name: 'app_responsable_club_commentaire_index', requirements: ['userId' => '\d+'], methods: ['GET'])]
    public function index(
        int $userId,
        CommentaireRepository $commentaireRepository,
        UserRepository $userRepository
    ): Response {
        $user = $userRepository->find($userId);
        if (!$user || $user->getRole() !== 'responsable_club') {
            throw $this->createAccessDeniedException('Accès réservé aux responsables de club.');
        }

        $commentaires = $commentaireRepository->createQueryBuilder('c')
            ->orderBy('c.dateCommentaire', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('commentaire/responsable_club_commentaire_index.html.twig', [
            'commentaires' => $commentaires,
            'userId' => $userId,
            'currentUser' => $user,
        ]);
    }

    // ✅ SUPPRIMER UN COMMENTAIRE (supervision)
    #[Route('/{userId}/delete/{commentaireId}', name: 'app_responsable_club_commentaire_delete', requirements: ['userId' => '\d+', 'commentaireId' => '\d+'], methods: ['GET'])]
    public function delete(
        int $userId,
        int $commentaireId,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $user = $userRepository->find($userId);
        if (!$user || $user->getRole() !== 'responsable_club') {
            throw $this->createAccessDeniedException('Accès réservé aux responsables de club.');
        }

        $commentaire = $em->getRepository(Commentaire::class)->find($commentaireId);
        if (!$commentaire) {
            throw $this->createNotFoundException('Commentaire introuvable.');
        }

        $em->remove($commentaire);
        $em->flush();

        return $this->redirectToRoute('app_responsable_club_commentaire_index', [
            'userId' => $userId,
        ]);
    }

    #[Route('/{userId}/reply/{commentaireId}', name: 'app_responsable_club_commentaire_reply', requirements: ['userId' => '\d+', 'commentaireId' => '\d+'], methods: ['GET', 'POST'])]
    public function reply(
        int $userId,
        int $commentaireId,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        NotificationService $notificationService
    ): Response {
        $user = $userRepository->find($userId);
        if (!$user || $user->getRole() !== 'responsable_club') {
            throw $this->createAccessDeniedException('Accès réservé aux responsables de club.');
        }

        $parentCommentaire = $em->getRepository(Commentaire::class)->find($commentaireId);
        if (!$parentCommentaire) {
            throw $this->createNotFoundException('Commentaire introuvable.');
        }

        $reponse = new Commentaire();
        $reponse->setUser($user);
        $reponse->setPublication($parentCommentaire->getPublication());

        $form = $this->createForm(CommentaireType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($reponse);
            $em->flush();

            // 🔔 Notifier l'auteur du commentaire parent que le responsable a répondu
            $auteurCommentaire = $parentCommentaire->getUser();
            if ($auteurCommentaire && $auteurCommentaire->getId() !== $userId) {
                try {
                    $notificationService->notifierReponse(
                        $user,               // responsable qui répond
                        $auteurCommentaire,  // membre auteur du commentaire
                        $parentCommentaire->getPublication()
                    );
                } catch (\Exception $e) {
                    // Mercure peut ne pas être dispo — on continue sans bloquer
                }
            }

            return $this->redirectToRoute('app_responsable_club_commentaire_index', [
                'userId' => $userId,
            ]);
        }

        return $this->render('commentaire/responsable_club_commentaire_reply.html.twig', [
            'form' => $form->createView(),
            'parentCommentaire' => $parentCommentaire,
            'userId' => $userId,
            'currentUser' => $user,
        ]);
    }
}