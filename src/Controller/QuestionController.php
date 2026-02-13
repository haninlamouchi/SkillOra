<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\OptionQuestion;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Form\QuestionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/formation/{formationId}/quiz/{quizId}/question')]
final class QuestionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    private function resolveParents(int $formationId, int $quizId): array
    {
        $formation = $this->em->getRepository(Formation::class)->find($formationId);
        if (!$formation) {
            throw $this->createNotFoundException('Formation introuvable.');
        }

        $quiz = $this->em->getRepository(Quiz::class)->find($quizId);
        if (!$quiz || $quiz->getFormation() !== $formation) {
            throw $this->createNotFoundException('Quiz introuvable pour cette formation.');
        }

        return [$formation, $quiz];
    }

    // ──────────────────────────────────────────────
    //  NEW QUESTION (with 4 pre-populated options)
    // ──────────────────────────────────────────────

    #[Route('/new', name: 'app_question_new', methods: ['GET', 'POST'])]
    public function new(int $formationId, int $quizId, Request $request): Response
    {
        [$formation, $quiz] = $this->resolveParents($formationId, $quizId);

        $question = new Question();
        $question->setQuiz($quiz);

        // Pre-populate 4 empty options (A, B, C, D)
        for ($i = 1; $i <= 4; $i++) {
            $option = new OptionQuestion();
            $option->setOrdre($i);
            $option->setEstCorrect(false);
            $question->addOptionQuestion($option);
        }

        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($question);

            // Update quiz question count
            $quiz->setNbQuestions($quiz->getQuestions()->count() + 1);
            $this->em->flush();

            $this->addFlash('success', 'Question ajoutée avec succès !');
            return $this->redirectToRoute('app_quiz_show', [
                'formationId' => $formation->getId(),
                'id' => $quiz->getId(),
            ]);
        }

        return $this->render('backoffice/question/new.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
            'form' => $form,
        ]);
    }

    // ──────────────────────────────────────────────
    //  EDIT QUESTION
    // ──────────────────────────────────────────────

    #[Route('/{id}/edit', name: 'app_question_edit', methods: ['GET', 'POST'])]
    public function edit(int $formationId, int $quizId, Question $question, Request $request): Response
    {
        [$formation, $quiz] = $this->resolveParents($formationId, $quizId);

        if ($question->getQuiz() !== $quiz) {
            throw $this->createNotFoundException('Question introuvable pour ce quiz.');
        }

        // Ensure we have exactly 4 options (pad if less)
        $currentCount = $question->getOptionQuestions()->count();
        for ($i = $currentCount + 1; $i <= 4; $i++) {
            $option = new OptionQuestion();
            $option->setOrdre($i);
            $option->setEstCorrect(false);
            $question->addOptionQuestion($option);
        }

        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Question modifiée avec succès !');
            return $this->redirectToRoute('app_quiz_show', [
                'formationId' => $formation->getId(),
                'id' => $quiz->getId(),
            ]);
        }

        return $this->render('backoffice/question/edit.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
            'question' => $question,
            'form' => $form,
        ]);
    }

    // ──────────────────────────────────────────────
    //  DELETE QUESTION
    // ──────────────────────────────────────────────

    #[Route('/{id}/delete', name: 'app_question_delete', methods: ['POST'])]
    public function delete(int $formationId, int $quizId, Question $question, Request $request): Response
    {
        [$formation, $quiz] = $this->resolveParents($formationId, $quizId);

        if ($this->isCsrfTokenValid('delete_question' . $question->getId(), $request->request->get('_token'))) {
            $quiz->removeQuestion($question);
            $this->em->remove($question);

            // Update quiz question count
            $quiz->setNbQuestions(max(0, $quiz->getQuestions()->count()));
            $this->em->flush();

            $this->addFlash('success', 'Question supprimée.');
        }

        return $this->redirectToRoute('app_quiz_show', [
            'formationId' => $formation->getId(),
            'id' => $quiz->getId(),
        ]);
    }
}
