<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\Quiz;
use App\Form\QuizType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/formation/{formationId}/quiz')]
final class QuizController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    // ──────────────────────────────────────────────
    //  LIST QUIZZES FOR A FORMATION
    // ──────────────────────────────────────────────

    #[Route('', name: 'app_quiz_index', methods: ['GET'])]
    public function index(int $formationId): Response
    {
        $formation = $this->em->getRepository(Formation::class)->find($formationId);
        if (!$formation) {
            throw $this->createNotFoundException('Formation introuvable.');
        }

        return $this->render('backoffice/quiz/index.html.twig', [
            'formation' => $formation,
            'quizzes' => $formation->getQuizzes(),
        ]);
    }

    // ──────────────────────────────────────────────
    //  CREATE QUIZ (Symfony Form)
    // ──────────────────────────────────────────────

    #[Route('/new', name: 'app_quiz_new', methods: ['GET', 'POST'])]
    public function new(int $formationId, Request $request): Response
    {
        $formation = $this->em->getRepository(Formation::class)->find($formationId);
        if (!$formation) {
            throw $this->createNotFoundException('Formation introuvable.');
        }

        $quiz = new Quiz();
        $quiz->setFormation($formation);

        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quiz->setNbQuestions(0);
            $this->em->persist($quiz);
            $this->em->flush();

            $this->addFlash('success', 'Quiz créé avec succès ! Ajoutez maintenant des questions.');
            return $this->redirectToRoute('app_quiz_show', [
                'formationId' => $formation->getId(),
                'id' => $quiz->getId(),
            ]);
        }

        return $this->render('backoffice/quiz/new.html.twig', [
            'formation' => $formation,
            'form' => $form,
        ]);
    }

    // ──────────────────────────────────────────────
    //  SHOW QUIZ (with questions list)
    // ──────────────────────────────────────────────

    #[Route('/{id}', name: 'app_quiz_show', methods: ['GET'])]
    public function show(int $formationId, Quiz $quiz): Response
    {
        $formation = $this->em->getRepository(Formation::class)->find($formationId);
        if (!$formation || $quiz->getFormation() !== $formation) {
            throw $this->createNotFoundException('Quiz introuvable pour cette formation.');
        }

        return $this->render('backoffice/quiz/show.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
        ]);
    }

    // ──────────────────────────────────────────────
    //  EDIT QUIZ (Symfony Form)
    // ──────────────────────────────────────────────

    #[Route('/{id}/edit', name: 'app_quiz_edit', methods: ['GET', 'POST'])]
    public function edit(int $formationId, Quiz $quiz, Request $request): Response
    {
        $formation = $this->em->getRepository(Formation::class)->find($formationId);
        if (!$formation || $quiz->getFormation() !== $formation) {
            throw $this->createNotFoundException('Quiz introuvable pour cette formation.');
        }

        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Quiz modifié avec succès !');
            return $this->redirectToRoute('app_quiz_show', [
                'formationId' => $formation->getId(),
                'id' => $quiz->getId(),
            ]);
        }

        return $this->render('backoffice/quiz/edit.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
            'form' => $form,
        ]);
    }

    // ──────────────────────────────────────────────
    //  DELETE QUIZ
    // ──────────────────────────────────────────────

    #[Route('/{id}/delete', name: 'app_quiz_delete', methods: ['POST'])]
    public function delete(int $formationId, Quiz $quiz, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete_quiz' . $quiz->getId(), $request->request->get('_token'))) {
            $this->em->remove($quiz);
            $this->em->flush();

            $this->addFlash('success', 'Quiz supprimé avec succès.');
        }

        return $this->redirectToRoute('app_formation_show', ['id' => $formationId]);
    }
}
