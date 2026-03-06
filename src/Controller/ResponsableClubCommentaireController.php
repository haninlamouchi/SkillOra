<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Form\CommentaireType;
use App\Repository\CommentaireRepository;
use App\Repository\ClubRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/responsable-club/commentaire')]
#[IsGranted('ROLE_RESPONSABLE_CLUB')]
class ResponsableClubCommentaireController extends AbstractController
{
    private function getCurrentResponsable(): \App\Entity\User
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }
        if ($user->getRole() !== 'responsable_club') {
            throw $this->createAccessDeniedException('Accès réservé aux responsables de club.');
        }
        return $user;
    }

    // ✅ PAGE DE SUPERVISION : COMMENTAIRES DES MEMBRES DU CLUB
    #[Route('/', name: 'app_responsable_club_commentaire_index', methods: ['GET'])]
    public function index(
        CommentaireRepository $commentaireRepository,
        ClubRepository $clubRepo
    ): Response {
        $currentUser = $this->getCurrentResponsable();
        
        // Récupérer le club dont le responsable est responsable
        $club = $clubRepo->findOneBy(['responsable' => $currentUser]);
        
        if (!$club) {
            $this->addFlash('warning', 'Vous devez être responsable d\'un club pour accéder à cette page.');
            return $this->redirectToRoute('front_home_user', ['userId' => $currentUser->getId()]);
        }
        
        // Récupérer les IDs des membres du club
        $clubMembers = $club->getMembres();
        $memberIds = [];
        foreach ($clubMembers as $member) {
            $memberIds[] = $member->getId();
        }
        
        // Ajouter le responsable lui-même à la liste
        $memberIds[] = $currentUser->getId();

        // Filtrer les commentaires par les membres du club
        $commentaires = $commentaireRepository->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->where('u.id IN (:memberIds)')
            ->setParameter('memberIds', $memberIds)
            ->orderBy('c.dateCommentaire', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('commentaire/responsable_club_commentaire_index.html.twig', [
            'commentaires' => $commentaires,
            'currentUser' => $currentUser,
            'club' => $club,
        ]);
    }

    // ✅ SUPPRIMER UN COMMENTAIRE (supervision)
    #[Route('/delete/{commentaireId}', name: 'app_responsable_club_commentaire_delete', requirements: ['commentaireId' => '\d+'], methods: ['GET'])]
    public function delete(
        int $commentaireId,
        EntityManagerInterface $em,
        ClubRepository $clubRepo
    ): Response {
        $currentUser = $this->getCurrentResponsable();

        $commentaire = $em->getRepository(Commentaire::class)->find($commentaireId);
        if (!$commentaire) {
            throw $this->createNotFoundException('Commentaire introuvable.');
        }

        // Vérifier que l'auteur du commentaire est membre du club du responsable
        $club = $clubRepo->findOneBy(['responsable' => $currentUser]);
        if ($club) {
            $author = $commentaire->getUser();
            if ($author && !$club->getMembres()->contains($author) && $author->getId() !== $currentUser->getId()) {
                $this->addFlash('danger', 'Vous ne pouvez supprimer que les commentaires des membres de votre club.');
                return $this->redirectToRoute('app_responsable_club_commentaire_index');
            }
        }

        $em->remove($commentaire);
        $em->flush();

        return $this->redirectToRoute('app_responsable_club_commentaire_index');
    }

    #[Route('/reply/{commentaireId}', name: 'app_responsable_club_commentaire_reply', requirements: ['commentaireId' => '\d+'], methods: ['GET', 'POST'])]
    public function reply(
        int $commentaireId,
        Request $request,
        EntityManagerInterface $em,
        ClubRepository $clubRepo,
        NotificationService $notificationService
    ): Response {
        $currentUser = $this->getCurrentResponsable();

        $parentCommentaire = $em->getRepository(Commentaire::class)->find($commentaireId);
        if (!$parentCommentaire) {
            throw $this->createNotFoundException('Commentaire introuvable.');
        }

        // Vérifier que l'auteur du commentaire est membre du club du responsable
        $club = $clubRepo->findOneBy(['responsable' => $currentUser]);
        if ($club) {
            $author = $parentCommentaire->getUser();
            if ($author && !$club->getMembres()->contains($author) && $author->getId() !== $currentUser->getId()) {
                $this->addFlash('danger', 'Vous ne pouvez répondre qu\'aux commentaires des membres de votre club.');
                return $this->redirectToRoute('app_responsable_club_commentaire_index');
            }
        }

        $reponse = new Commentaire();
        $reponse->setUser($currentUser);
        $reponse->setPublication($parentCommentaire->getPublication());

        $form = $this->createForm(CommentaireType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($reponse);
            $em->flush();

            // 🔔 Notifier l'auteur du commentaire parent que le responsable a répondu
            $auteurCommentaire = $parentCommentaire->getUser();
            $publication = $parentCommentaire->getPublication();
            if ($auteurCommentaire && $publication && $auteurCommentaire->getId() !== $currentUser->getId()) {
                try {
                    $notificationService->notifierReponse(
                        $currentUser,        // responsable qui répond
                        $auteurCommentaire,  // membre auteur du commentaire
                        $publication
                    );
                } catch (\Exception $e) {
                    // Mercure peut ne pas être dispo — on continue sans bloquer
                }
            }

            return $this->redirectToRoute('app_responsable_club_commentaire_index');
        }

        return $this->render('commentaire/responsable_club_commentaire_reply.html.twig', [
            'form' => $form->createView(),
            'parentCommentaire' => $parentCommentaire,
            'currentUser' => $currentUser,
            'club' => $club,
        ]);
    }
}