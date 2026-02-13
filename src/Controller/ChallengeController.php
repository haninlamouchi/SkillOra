<?php

namespace App\Controller;

use App\Entity\Challenge;
use App\Form\ChallengeType;
use App\Repository\ChallengeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Service\NotificationService;
use App\Entity\Groupe;



#[Route('/challenge')]
final class ChallengeController extends AbstractController
{
    #[Route(name: 'app_challenge_index', methods: ['GET'])]
    public function index(ChallengeRepository $challengeRepository): Response
    {
        return $this->render('challenge/index.html.twig', [
            'challenges' => $challengeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_challenge_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, NotificationService $notification): Response
    {
        $challenge = new Challenge();
        $form = $this->createForm(ChallengeType::class, $challenge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            $cahierFile = $form->get('fichierCahierCharges')->getData();

            if ($imageFile) {

            $newFilename = uniqid().'.'.$imageFile->guessExtension();
            try {
            $imageFile->move(
                $this->getParameter('uploads_directory'),
                $newFilename
            );
            $challenge->setImage($newFilename);

            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
            }
        }
            if ($cahierFile) {

            $newFilename = uniqid().'.'.$cahierFile->guessExtension();
             try {
            $cahierFile->move(
                $this->getParameter('uploads_directory'),
                $newFilename
            );
            $challenge->setFichierCahierCharges($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload du fichier PDF.');
            }
            }
            $entityManager->persist($challenge);
            $entityManager->flush();

            //  NOTIFICATION EMAIL AUX MEMBRES
            $groupes = $entityManager->getRepository(Groupe::class)->findAll();

            foreach ($groupes as $groupe) {
                foreach ($groupe->getMembres() as $membre) {

                    $user = $membre->getUser();

                    if ($user && $user->getEmail()) {
                        $notification->envoyerEmail(
                        $user->getEmail(),
                        'Nouveau Challenge disponible',
                        'Un nouveau challenge a été créé : '.$challenge->getTitre()
                    );
                }
            }
        }




            return $this->redirectToRoute('app_challenge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('challenge/new.html.twig', [
            'challenge' => $challenge,
            'form' => $form,
        ]);
    }

    #[Route('/cherch', name: 'challenge_search')]
    public function search(Request $request, ChallengeRepository $challengeRepository): Response
    {
        $titre = $request->query->get('titre');
        $dateDebut = $request->query->get('dateDebut') ? new \DateTime($request->query->get('dateDebut')) : null;
        $dateFin = $request->query->get('dateFin') ? new \DateTime($request->query->get('dateFin')) : null;

        $challenges = $challengeRepository->searchChallenges($titre, $dateDebut, $dateFin);

        return $this->render('challenge/index.html.twig', [
            'challenges' => $challenges,
        ]);
    }

    #[Route('/{id}', name: 'app_challenge_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Challenge $challenge): Response

    {
        if (!$challenge) {
            throw $this->createNotFoundException('Challenge introuvable');
        }
        return $this->render('challenge/show.html.twig', [
            'challenge' => $challenge,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_challenge_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Challenge $challenge, EntityManagerInterface $entityManager): Response
    {
        if (!$challenge) {
            throw $this->createNotFoundException('Challenge introuvable');
        }
        $form = $this->createForm(ChallengeType::class, $challenge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_challenge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('challenge/edit.html.twig', [
            'challenge' => $challenge,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_challenge_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Challenge $challenge, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$challenge->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($challenge);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_challenge_index', [], Response::HTTP_SEE_OTHER);
    }

}
