<?php

namespace App\Controller;

use App\Entity\LivrableChallenge;
use App\Form\LivrableChallengeType;
use App\Repository\LivrableChallengeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/livrable/challenge')]
final class LivrableChallengeController extends AbstractController
{
    #[Route(name: 'app_livrable_challenge_index', methods: ['GET'])]
    public function index(LivrableChallengeRepository $livrableChallengeRepository): Response
    {
        return $this->render('livrable_challenge/index.html.twig', [
            'livrable_challenges' => $livrableChallengeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_livrable_challenge_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $livrableChallenge = new LivrableChallenge();
        $form = $this->createForm(LivrableChallengeType::class, $livrableChallenge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('fichier')->getData();

        if ($file) {
            $newFilename = uniqid().'.'.$file->guessExtension();

            $file->move(
                $this->getParameter('uploads_directory'),
                $newFilename
            );

            $livrableChallenge->setFichier($newFilename);
        }

        $livrableChallenge->setDateSoumission(new \DateTimeImmutable());

            $entityManager->persist($livrableChallenge);
            $entityManager->flush();

            return $this->redirectToRoute('app_livrable_challenge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('livrable_challenge/new.html.twig', [
            'livrable_challenge' => $livrableChallenge,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_livrable_challenge_show', methods: ['GET'])]
    public function show(LivrableChallenge $livrableChallenge): Response
    {
        return $this->render('livrable_challenge/show.html.twig', [
            'livrable_challenge' => $livrableChallenge,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_livrable_challenge_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, LivrableChallenge $livrableChallenge, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LivrableChallengeType::class, $livrableChallenge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_livrable_challenge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('livrable_challenge/edit.html.twig', [
            'livrable_challenge' => $livrableChallenge,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_livrable_challenge_delete', methods: ['POST'])]
    public function delete(Request $request, LivrableChallenge $livrableChallenge, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$livrableChallenge->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($livrableChallenge);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_livrable_challenge_index', [], Response::HTTP_SEE_OTHER);
    }
}
