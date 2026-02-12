<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Publication;
use App\Enum\StatusPublication;
use App\Form\CommentaireType;
use App\Repository\PublicationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/forum')]
class ForumController extends AbstractController
{
    /**
     * Résout l'utilisateur depuis ?u= — retourne null si absent (visiteur).
     * Lève une exception si l'ID est fourni mais n'existe pas en base.
     */
    private function resolveUser(Request $request, UserRepository $userRepository): ?\App\Entity\User
    {
        $userId = $request->query->get('u');
        if (!$userId) {
            return null;
        }

        $user = $userRepository->find((int) $userId);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        return $user;
    }

    // ✅ LISTE DES PUBLICATIONS — page publique
    #[Route('/', name: 'app_forum_index', methods: ['GET'])]
    public function index(PublicationRepository $publicationRepository, Request $request, UserRepository $userRepository): Response
    {
        // Valider l'utilisateur si ?u= est fourni (lève 404 si ID inexistant)
        $currentUser = $this->resolveUser($request, $userRepository);

        $publications = $publicationRepository->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', StatusPublication::PUBLIE)
            ->orderBy('p.datePublication', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('forum/index.html.twig', [
            'publications' => $publications,
            'currentUser' => $currentUser,
        ]);
    }

    // ✅ DÉTAIL PUBLICATION + COMMENTAIRES
    #[Route('/show/{id}', name: 'app_forum_show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $publication = $em->getRepository(Publication::class)->find($id);
        if (!$publication) {
            throw $this->createNotFoundException('Publication introuvable.');
        }

        $currentUser = $this->resolveUser($request, $userRepository);

        $form = null;
        if ($currentUser) {
            $commentaire = new Commentaire();
            $form = $this->createForm(CommentaireType::class, $commentaire);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $commentaire->setUser($currentUser);
                $commentaire->setPublication($publication);
                $em->persist($commentaire);
                $em->flush();

                return $this->redirectToRoute('app_forum_show', [
                    'id' => $publication->getId(),
                    'u' => $currentUser->getId(),
                ]);
            }
        }

        return $this->render('forum/show.html.twig', [
            'publication' => $publication,
            'commentForm' => $form ? $form->createView() : null,
            'currentUser' => $currentUser,
        ]);
    }

    // ✅ MODIFIER SON COMMENTAIRE
    #[Route('/commentaire/edit/{commentaireId}', name: 'app_forum_commentaire_edit', requirements: ['commentaireId' => '\d+'], methods: ['GET', 'POST'])]
    public function editCommentaire(
        int $commentaireId,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $commentaire = $em->getRepository(Commentaire::class)->find($commentaireId);
        if (!$commentaire) {
            throw $this->createNotFoundException('Commentaire introuvable.');
        }

        $userId = $request->query->get('u');
        if (!$userId) {
            throw $this->createAccessDeniedException('User ID manquant.');
        }

        $user = $userRepository->find((int) $userId);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        if ($commentaire->getUser()->getId() !== (int) $userId) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres commentaires.');
        }

        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_forum_show', [
                'id' => $commentaire->getPublication()->getId(),
                'u' => $userId,
            ]);
        }

        return $this->render('forum/edit_commentaire.html.twig', [
            'form' => $form->createView(),
            'commentaire' => $commentaire,
            'userId' => $userId,
        ]);
    }

    // ✅ SUPPRIMER SON COMMENTAIRE
    #[Route('/commentaire/delete/{commentaireId}', name: 'app_forum_commentaire_delete', requirements: ['commentaireId' => '\d+'], methods: ['GET'])]
    public function deleteCommentaire(
        int $commentaireId,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $commentaire = $em->getRepository(Commentaire::class)->find($commentaireId);
        if (!$commentaire) {
            throw $this->createNotFoundException('Commentaire introuvable.');
        }

        $userId = $request->query->get('u');
        if (!$userId) {
            throw $this->createAccessDeniedException('User ID manquant.');
        }

        $user = $userRepository->find((int) $userId);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        if ($commentaire->getUser()->getId() !== (int) $userId) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres commentaires.');
        }

        $publicationId = $commentaire->getPublication()->getId();
        $em->remove($commentaire);
        $em->flush();

        return $this->redirectToRoute('app_forum_show', [
            'id' => $publicationId,
            'u' => $userId,
        ]);
    }
}