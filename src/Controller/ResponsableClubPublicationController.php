<?php

namespace App\Controller;

use App\Entity\Publication;
use App\Enum\StatusPublication;
use App\Form\PublicationType;
use App\Repository\PublicationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/responsable-club/publication')]
class ResponsableClubPublicationController extends AbstractController
{
    /**
     * Résout l'utilisateur courant depuis ?u= et vérifie qu'il est responsable_club.
     */
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

    #[Route('/', name: 'app_responsable_club_publication_index')]
    public function index(PublicationRepository $repo, Request $request, UserRepository $userRepository): Response
    {
        $currentUser = $this->resolveResponsable($request, $userRepository);

        return $this->render(
            'publication/responsable_club_publication_index.html.twig',
            [
                'publications' => $repo->findAll(),
                'currentUser' => $currentUser,
            ]
        );
    }

    #[Route('/show/{id}', name: 'app_responsable_club_publication_show')]
    public function show(Publication $publication, Request $request, UserRepository $userRepository): Response
    {
        $currentUser = $this->resolveResponsable($request, $userRepository);

        return $this->render(
            'publication/Responsable club publication show.html.twig',
            [
                'publication' => $publication,
                'currentUser' => $currentUser,
            ]
        );
    }

    #[Route('/new/{id}', name: 'app_responsable_club_publication_new')]
    public function new(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        SluggerInterface $slugger
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
        $publication->setStatus(StatusPublication::PUBLIE);

        $form = $this->createForm(PublicationType::class, $publication, [
            'show_status' => false
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $publication->setStatus(StatusPublication::PUBLIE);

            $fichierFile = $form->get('fichier')->getData();
            if ($fichierFile) {
                $originalFilename = pathinfo($fichierFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$fichierFile->guessExtension();

                try {
                    $fichierFile->move(
                        $this->getParameter('publications_directory'),
                        $newFilename
                    );
                    $publication->setFichier($newFilename);
                } catch (FileException $e) {
                }
            }

            $em->persist($publication);
            $em->flush();

            return $this->redirectToRoute('app_responsable_club_publication_index', ['u' => $id]);
        }

        return $this->render(
            'publication/Responsable club publication new.html.twig',
            [
                'form' => $form->createView(),
                'currentUser' => $user,
            ]
        );
    }

    #[Route('/edit/{id}', name: 'app_responsable_club_publication_edit')]
    public function edit(
        Publication $publication,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        SluggerInterface $slugger
    ): Response {
        $currentUser = $this->resolveResponsable($request, $userRepository);

        $form = $this->createForm(PublicationType::class, $publication, [
            'show_status' => true
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fichierFile = $form->get('fichier')->getData();
            if ($fichierFile) {
                if ($publication->getFichier()) {
                    $oldFile = $this->getParameter('publications_directory').'/'.$publication->getFichier();
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                $originalFilename = pathinfo($fichierFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$fichierFile->guessExtension();

                try {
                    $fichierFile->move(
                        $this->getParameter('publications_directory'),
                        $newFilename
                    );
                    $publication->setFichier($newFilename);
                } catch (FileException $e) {
                }
            }

            $em->flush();

            return $this->redirectToRoute('app_responsable_club_publication_index', ['u' => $currentUser->getId()]);
        }

        return $this->render(
            'publication/Responsable club publication edit.html.twig',
            [
                'publication' => $publication,
                'form' => $form->createView(),
                'currentUser' => $currentUser,
            ]
        );
    }

    #[Route('/delete/{id}', name: 'app_responsable_club_publication_delete')]
    public function delete(
        Publication $publication,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $currentUser = $this->resolveResponsable($request, $userRepository);

        if ($publication->getFichier()) {
            $filePath = $this->getParameter('publications_directory').'/'.$publication->getFichier();
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $em->remove($publication);
        $em->flush();

        return $this->redirectToRoute('app_responsable_club_publication_index', ['u' => $currentUser->getId()]);
    }
    
    #[Route('/valider/{id}', name: 'app_responsable_club_publication_valider')]
    public function valider(
        Publication $publication,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $currentUser = $this->resolveResponsable($request, $userRepository);

        $publication->setStatus(StatusPublication::PUBLIE);
        $em->flush();

        return $this->redirectToRoute('app_responsable_club_publication_index', ['u' => $currentUser->getId()]);
    }
}