<?php

namespace App\Controller;

use App\Entity\Publication;
use App\Enum\StatusPublication;
use App\Form\PublicationType;
use App\Repository\PublicationRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/publication')]
class PublicationController extends AbstractController
{
    /**
     * Résout un utilisateur depuis ?u= en acceptant les rôles membre ET etudiant
     * (etudiant = ancien nom, membre = nouveau nom — les deux sont acceptés)
     */
    private function resolveMembre(Request $request, UserRepository $userRepository): \App\Entity\User
    {
        $userId = $request->query->get('u');
        if (!$userId) {
            throw $this->createNotFoundException('Paramètre utilisateur manquant (?u=).');
        }

        $user = $userRepository->find((int) $userId);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        // ✅ Accepte les deux rôles : membre (nouveau) et etudiant (ancien, rétrocompat)
        if (!in_array($user->getRole(), ['membre', 'etudiant'])) {
            throw $this->createAccessDeniedException('Accès réservé aux membres.');
        }

        return $user;
    }

    // ✅ MES PUBLICATIONS
    #[Route('/', name: 'app_publication_index')]
    public function index(
        PublicationRepository $repo,
        Request $request,
        UserRepository $userRepository,
        PaginatorInterface $paginator
    ): Response {
        $currentUser = $this->resolveMembre($request, $userRepository);

        $queryBuilder = $repo->createQueryBuilder('p')
            ->where('p.user = :user')
            ->setParameter('user', $currentUser)
            ->orderBy('p.datePublication', 'DESC');

        $publications = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            6 // publications par page
        );

        return $this->render('publication/index.html.twig', [
            'publications' => $publications,
            'currentUser'  => $currentUser,
        ]);
    }

    // ✅ VOIR UNE PUBLICATION
    #[Route('/show/{id}', name: 'app_publication_show')]
    public function show(Publication $publication, Request $request, UserRepository $userRepository): Response
    {
        $currentUser = $this->resolveMembre($request, $userRepository);

        $pubUser = $publication->getUser();
        if (!$pubUser || $pubUser->getId() !== $currentUser->getId()) {
            throw $this->createNotFoundException('Publication introuvable.');
        }

        return $this->render('publication/show.html.twig', [
            'publication' => $publication,
            'currentUser' => $currentUser,
        ]);
    }

    // ✅ NOUVELLE PUBLICATION — route /new/{id} (id = userId dans l'URL)
    #[Route('/new/{id}', name: 'app_publication_new')]
    public function new(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        NotificationService $notificationService
    ): Response {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        // ✅ Accepte membre ET etudiant
        if (!in_array($user->getRole(), ['membre', 'etudiant'])) {
            throw $this->createAccessDeniedException('Accès réservé aux membres.');
        }

        $publication = new Publication();
        $publication->setUser($user);
        $publication->setStatus(StatusPublication::EN_ATTENTE);

        $form = $this->createForm(PublicationType::class, $publication, [
            'show_status' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $publication->setStatus(StatusPublication::EN_ATTENTE);
            $em->persist($publication);
            $em->flush();

            // 🔔 Notifier les responsables des clubs du membre
            try {
                $notificationService->notifierNouvellePublicationMembre($user, $publication);
            } catch (\Exception $e) {
                error_log('[Notification] Erreur : ' . $e->getMessage());
            }

            $this->addFlash('success', '✅ Publication soumise ! Elle sera visible après validation par un responsable.');

            return $this->redirectToRoute('app_publication_index', ['u' => $id]);
        }

        return $this->render('publication/new.html.twig', [
            'form'        => $form->createView(),
            'currentUser' => $user,
        ]);
    }

    // ✅ MODIFIER SA PUBLICATION
    #[Route('/edit/{id}', name: 'app_publication_edit')]
    public function edit(
        Publication $publication,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $currentUser = $this->resolveMembre($request, $userRepository);

        $pubUser = $publication->getUser();
        if (!$pubUser || $pubUser->getId() !== $currentUser->getId()) {
            throw $this->createNotFoundException('Publication introuvable.');
        }

        $form = $this->createForm(PublicationType::class, $publication, [
            'show_status' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Repasse en EN_ATTENTE après modification
            $publication->setStatus(StatusPublication::EN_ATTENTE);
            $em->flush();

            $this->addFlash('success', '✅ Publication modifiée. Elle sera re-validée par un responsable.');

            return $this->redirectToRoute('app_publication_index', ['u' => $currentUser->getId()]);
        }

        return $this->render('publication/edit.html.twig', [
            'publication'  => $publication,
            'form'         => $form->createView(),
            'currentUser'  => $currentUser,
        ]);
    }

    // ✅ SUPPRIMER SA PUBLICATION
    #[Route('/delete/{id}', name: 'app_publication_delete')]
    public function delete(
        Publication $publication,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $currentUser = $this->resolveMembre($request, $userRepository);

        $pubUser = $publication->getUser();
        if (!$pubUser || $pubUser->getId() !== $currentUser->getId()) {
            throw $this->createNotFoundException('Publication introuvable.');
        }

        $em->remove($publication);
        $em->flush();

        $this->addFlash('success', '🗑️ Publication supprimée.');

        return $this->redirectToRoute('app_publication_index', ['u' => $currentUser->getId()]);
    }
}