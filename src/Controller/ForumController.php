<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Publication;
use App\Enum\StatusPublication;
use App\Form\CommentaireType;
use App\Repository\PublicationRepository;
use App\Repository\UserRepository;
use App\Service\BrevoMailingService;
use App\Service\ModerationService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/forum')]
class ForumController extends AbstractController
{
    private function resolveUser(Request $request, UserRepository $userRepository): ?\App\Entity\User
    {
        $userId = $request->query->get('u');
        if (!$userId) return null;
        $user = $userRepository->find((int) $userId);
        if (!$user) throw $this->createNotFoundException('Utilisateur introuvable.');
        return $user;
    }

    #[Route('/', name: 'app_forum_index', methods: ['GET'])]
    public function index(
        PublicationRepository $publicationRepository,
        Request $request,
        UserRepository $userRepository,
        NotificationService $notificationService
    ): Response {
        $currentUser  = $this->resolveUser($request, $userRepository);
        $publications = $publicationRepository->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', StatusPublication::PUBLIE)
            ->orderBy('p.datePublication', 'DESC')
            ->getQuery()->getResult();

        $notifs = $currentUser ? $notificationService->getNonLues($currentUser) : [];

        return $this->render('forum/index.html.twig', [
            'publications'         => $publications,
            'currentUser'          => $currentUser,
            'notificationsNonLues' => $notifs,
        ]);
    }

    #[Route('/show/{id}', name: 'app_forum_show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        NotificationService $notificationService,
        BrevoMailingService $brevoMailingService,
        ModerationService $moderationService
    ): Response {
        $publication = $em->getRepository(Publication::class)->find($id);
        if (!$publication) throw $this->createNotFoundException('Publication introuvable.');

        $currentUser = $this->resolveUser($request, $userRepository);

        $form = null;
        if ($currentUser) {
            $commentaire = new Commentaire();
            $form = $this->createForm(CommentaireType::class, $commentaire);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                // 🚫 Vérification modération OpenAI
                if ($moderationService->estInapproprie($commentaire->getContenu() ?? '')) {
                    $this->addFlash('error', '🚫 Votre commentaire a été rejeté car il contient des propos inappropriés.');
                    return $this->redirectToRoute('app_forum_show', [
                        'id' => $publication->getId(),
                        'u'  => $currentUser->getId(),
                    ]);
                }

                $commentaire->setUser($currentUser);
                $commentaire->setPublication($publication);
                $em->persist($commentaire);
                $em->flush();

                // 🔔 Notification Mercure → responsable
                $responsable = $publication->getUser();
                if ($responsable && $responsable->getId() !== $currentUser->getId()) {
                    try {
                        $notificationService->notifierCommentaire($currentUser, $responsable, $publication, $commentaire);
                    } catch (\Exception $e) {}
                }

                // 📧 Email admin via Brevo
                try {
                    $brevoMailingService->notifierNouveauCommentaire($commentaire);
                } catch (\Exception $e) {
                    error_log('[Brevo] Erreur commentaire : ' . $e->getMessage());
                }

                return $this->redirectToRoute('app_forum_show', [
                    'id' => $publication->getId(),
                    'u'  => $currentUser->getId(),
                ]);
            }
        }

        $notifs = $currentUser ? $notificationService->getNonLues($currentUser) : [];

        return $this->render('forum/show.html.twig', [
            'publication'          => $publication,
            'commentForm'          => $form ? $form->createView() : null,
            'currentUser'          => $currentUser,
            'notificationsNonLues' => $notifs,
        ]);
    }

    #[Route('/commentaire/edit/{commentaireId}', name: 'app_forum_commentaire_edit', requirements: ['commentaireId' => '\d+'], methods: ['GET', 'POST'])]
    public function editCommentaire(
        int $commentaireId,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        NotificationService $notificationService
    ): Response {
        $commentaire = $em->getRepository(Commentaire::class)->find($commentaireId);
        if (!$commentaire) throw $this->createNotFoundException('Commentaire introuvable.');

        $userId = $request->query->get('u');
        if (!$userId) throw $this->createAccessDeniedException('User ID manquant.');
        $user = $userRepository->find((int) $userId);
        if (!$user) throw $this->createNotFoundException('Utilisateur introuvable.');
        $commentUser = $commentaire->getUser();
        if (!$commentUser || $commentUser->getId() !== (int) $userId) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres commentaires.');
        }

        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $publication = $commentaire->getPublication();
            return $this->redirectToRoute('app_forum_show', [
                'id' => $publication?->getId() ?? 0,
                'u'  => $userId,
            ]);
        }

        $notifs = $notificationService->getNonLues($user);

        return $this->render('forum/edit_commentaire.html.twig', [
            'form'                 => $form->createView(),
            'commentaire'          => $commentaire,
            'userId'               => $userId,
            'currentUser'          => $user,
            'notificationsNonLues' => $notifs,
        ]);
    }

    #[Route('/commentaire/delete/{commentaireId}', name: 'app_forum_commentaire_delete', requirements: ['commentaireId' => '\d+'], methods: ['GET'])]
    public function deleteCommentaire(
        int $commentaireId,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $commentaire = $em->getRepository(Commentaire::class)->find($commentaireId);
        if (!$commentaire) throw $this->createNotFoundException('Commentaire introuvable.');

        $userId = $request->query->get('u');
        if (!$userId) throw $this->createAccessDeniedException('User ID manquant.');
        $user = $userRepository->find((int) $userId);
        if (!$user) throw $this->createNotFoundException('Utilisateur introuvable.');
        $commentUser = $commentaire->getUser();
        if (!$commentUser || $commentUser->getId() !== (int) $userId) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres commentaires.');
        }

        $publication = $commentaire->getPublication();
        $publicationId = $publication?->getId() ?? 0;
        $em->remove($commentaire);
        $em->flush();

        return $this->redirectToRoute('app_forum_show', [
            'id' => $publicationId,
            'u'  => $userId,
        ]);
    }
}