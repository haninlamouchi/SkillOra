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

#[Route('/publication')]
class PublicationController extends AbstractController
{
    private function resolveEtudiant(Request $request, UserRepository $userRepository): \App\Entity\User
    {
        $userId = $request->query->get('u');
        if (!$userId) {
            throw $this->createNotFoundException('Paramètre utilisateur manquant.');
        }
        $user = $userRepository->find((int) $userId);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }
        if ($user->getRole() !== 'etudiant') {
            throw $this->createNotFoundException('Accès réservé aux étudiants.');
        }
        return $user;
    }

    #[Route('/', name: 'app_publication_index')]
    public function index(PublicationRepository $repo, Request $request, UserRepository $userRepository): Response
    {
        $currentUser = $this->resolveEtudiant($request, $userRepository);

        return $this->render('publication/index.html.twig', [
            'publications' => $repo->findBy(['user' => $currentUser], ['datePublication' => 'DESC']),
            'currentUser' => $currentUser,
        ]);
    }

    #[Route('/show/{id}', name: 'app_publication_show')]
    public function show(Publication $publication, Request $request, UserRepository $userRepository): Response
    {
        $currentUser = $this->resolveEtudiant($request, $userRepository);

        if ($publication->getUser()->getId() !== $currentUser->getId()) {
            throw $this->createNotFoundException('Publication introuvable.');
        }

        return $this->render('publication/show.html.twig', [
            'publication' => $publication,
            'currentUser' => $currentUser,
        ]);
    }

    #[Route('/new/{id}', name: 'app_publication_new')]
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
        if ($user->getRole() !== 'etudiant') {
            throw $this->createNotFoundException('Accès réservé aux étudiants.');
        }

        $publication = new Publication();
        $publication->setUser($user);
        $publication->setStatus(StatusPublication::EN_ATTENTE);

        $form = $this->createForm(PublicationType::class, $publication, [
            'show_status' => false
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $publication->setStatus(StatusPublication::EN_ATTENTE);

            $fichierFile = $form->get('fichier')->getData();
            if ($fichierFile) {
                $originalFilename = pathinfo($fichierFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$fichierFile->guessExtension();
                try {
                    $fichierFile->move($this->getParameter('publications_directory'), $newFilename);
                    $publication->setFichier($newFilename);
                } catch (FileException $e) {}
            }

            $em->persist($publication);
            $em->flush();

            return $this->redirectToRoute('app_publication_index', ['u' => $id]);
        }

        return $this->render('publication/new.html.twig', [
            'form' => $form->createView(),
            'currentUser' => $user,
        ]);
    }

    #[Route('/edit/{id}', name: 'app_publication_edit')]
    public function edit(
        Publication $publication,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        SluggerInterface $slugger
    ): Response {
        $currentUser = $this->resolveEtudiant($request, $userRepository);

        if ($publication->getUser()->getId() !== $currentUser->getId()) {
            throw $this->createNotFoundException('Publication introuvable.');
        }

        $form = $this->createForm(PublicationType::class, $publication, [
            'show_status' => false
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $publication->setStatus(StatusPublication::EN_ATTENTE);

            $fichierFile = $form->get('fichier')->getData();
            if ($fichierFile) {
                if ($publication->getFichier()) {
                    $oldFile = $this->getParameter('publications_directory').'/'.$publication->getFichier();
                    if (file_exists($oldFile)) { unlink($oldFile); }
                }
                $originalFilename = pathinfo($fichierFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$fichierFile->guessExtension();
                try {
                    $fichierFile->move($this->getParameter('publications_directory'), $newFilename);
                    $publication->setFichier($newFilename);
                } catch (FileException $e) {}
            }

            $em->flush();
            return $this->redirectToRoute('app_publication_index', ['u' => $currentUser->getId()]);
        }

        return $this->render('publication/edit.html.twig', [
            'publication' => $publication,
            'form' => $form->createView(),
            'currentUser' => $currentUser,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_publication_delete')]
    public function delete(
        Publication $publication,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $currentUser = $this->resolveEtudiant($request, $userRepository);

        if ($publication->getUser()->getId() !== $currentUser->getId()) {
            throw $this->createNotFoundException('Publication introuvable.');
        }

        if ($publication->getFichier()) {
            $fichierPath = $this->getParameter('publications_directory').'/'.$publication->getFichier();
            if (file_exists($fichierPath)) { unlink($fichierPath); }
        }

        $em->remove($publication);
        $em->flush();

        return $this->redirectToRoute('app_publication_index', ['u' => $currentUser->getId()]);
    }
}