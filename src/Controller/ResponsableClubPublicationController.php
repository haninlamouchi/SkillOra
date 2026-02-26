<?php

namespace App\Controller;

use App\Entity\Publication;
use App\Enum\StatusPublication;
use App\Form\PublicationType;
use App\Repository\PublicationRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/responsable-club/publication')]
class ResponsableClubPublicationController extends AbstractController
{
    private function resolveResponsable(Request $request, UserRepository $userRepository): \App\Entity\User
    {
        $userId = $request->query->get('u');
        if (!$userId) {
            throw $this->createAccessDeniedException('Paramètre utilisateur manquant (?u=).');
        }

        $user = $userRepository->find((int) $userId);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        if ($user->getRole() !== 'responsable_club') {
            throw $this->createAccessDeniedException('Accès réservé aux responsables de club.');
        }

        return $user;
    }

    // ✅ TOUTES LES PUBLICATIONS (avec EN_ATTENTE pour validation)
    #[Route('/', name: 'app_responsable_club_publication_index')]
    public function index(PublicationRepository $repo, Request $request, UserRepository $userRepository): Response
    {
        $currentUser = $this->resolveResponsable($request, $userRepository);

        return $this->render('publication/responsable_club_publication_index.html.twig', [
            'publications' => $repo->findAll(),
            'currentUser'  => $currentUser,
        ]);
    }

    // ✅ VOIR UNE PUBLICATION
    #[Route('/show/{id}', name: 'app_responsable_club_publication_show')]
    public function show(Publication $publication, Request $request, UserRepository $userRepository): Response
    {
        $currentUser = $this->resolveResponsable($request, $userRepository);

        return $this->render('publication/responsable_club_publication_show.html.twig', [
            'publication' => $publication,
            'currentUser' => $currentUser,
        ]);
    }

    // ✅ NOUVELLE PUBLICATION — publiée directement par le responsable
    #[Route('/new/{id}', name: 'app_responsable_club_publication_new')]
    public function new(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }
        if ($user->getRole() !== 'responsable_club') {
            throw $this->createAccessDeniedException('Accès réservé aux responsables de club.');
        }

        $publication = new Publication();
        $publication->setUser($user);
        $publication->setStatus(StatusPublication::PUBLIE); // publiée directement

        $form = $this->createForm(PublicationType::class, $publication, [
            'show_status' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $publication->setStatus(StatusPublication::PUBLIE);
            $em->persist($publication);
            $em->flush();

            $this->addFlash('success', '✅ Publication publiée avec succès.');

            return $this->redirectToRoute('app_responsable_club_publication_index', ['u' => $id]);
        }

        return $this->render('publication/responsable_club_publication_new.html.twig', [
            'form'        => $form->createView(),
            'currentUser' => $user,
        ]);
    }

    // ✅ MODIFIER UNE PUBLICATION
    #[Route('/edit/{id}', name: 'app_responsable_club_publication_edit')]
    public function edit(
        Publication $publication,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $currentUser = $this->resolveResponsable($request, $userRepository);

        $form = $this->createForm(PublicationType::class, $publication, [
            'show_status' => true, // le responsable peut changer le status
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', '✅ Publication modifiée.');

            return $this->redirectToRoute('app_responsable_club_publication_index', ['u' => $currentUser->getId()]);
        }

        return $this->render('publication/responsable_club_publication_edit.html.twig', [
            'publication' => $publication,
            'form'        => $form->createView(),
            'currentUser' => $currentUser,
        ]);
    }

    // ✅ SUPPRIMER UNE PUBLICATION
    #[Route('/delete/{id}', name: 'app_responsable_club_publication_delete')]
    public function delete(
        Publication $publication,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $currentUser = $this->resolveResponsable($request, $userRepository);

        $em->remove($publication);
        $em->flush();

        $this->addFlash('success', '🗑️ Publication supprimée.');

        return $this->redirectToRoute('app_responsable_club_publication_index', ['u' => $currentUser->getId()]);
    }

    // ✅ VALIDER UNE PUBLICATION (EN_ATTENTE → PUBLIE)
    #[Route('/valider/{id}', name: 'app_responsable_club_publication_valider')]
    public function valider(
        Publication $publication,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        NotificationService $notificationService
    ): Response {
        $currentUser = $this->resolveResponsable($request, $userRepository);

        $publication->setStatus(StatusPublication::PUBLIE);
        $em->flush();

        // 🔔 Notifier l'auteur (membre) que sa publication est validée
        $auteur = $publication->getUser();
        if ($auteur && $auteur->getId() !== $currentUser->getId()) {
            try {
                $notificationService->notifierValidation(
                    $currentUser,  // responsable qui valide
                    $auteur,       // membre auteur
                    $publication
                );
            } catch (\Exception $e) {
                // Mercure peut ne pas être dispo — on continue sans bloquer
            }
        }

        $this->addFlash('success', '✅ Publication validée et publiée sur le forum.');

        return $this->redirectToRoute('app_responsable_club_publication_index', ['u' => $currentUser->getId()]);
    }

    // ✅ REJETER UNE PUBLICATION (PUBLIE → EN_ATTENTE)
    #[Route('/rejeter/{id}', name: 'app_responsable_club_publication_rejeter')]
    public function rejeter(
        Publication $publication,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $currentUser = $this->resolveResponsable($request, $userRepository);

        $publication->setStatus(StatusPublication::EN_ATTENTE);
        $em->flush();

        $this->addFlash('warning', '⚠️ Publication repassée en attente.');

        return $this->redirectToRoute('app_responsable_club_publication_index', ['u' => $currentUser->getId()]);
    }
}