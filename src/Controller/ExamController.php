<?php

namespace App\Controller;

use App\Entity\Exam;
use App\Entity\ExamChoice;
use App\Entity\ExamQuestion;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/responsable/formations/{formationId}/exam', name: 'resp_exam_')]
#[IsGranted('ROLE_RESPONSABLE_CLUB')]
class ExamController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private FormationRepository $formationRepo,
    ) {}

    // ──────────────────────────────────────────────
    //  GET  - Show exam builder
    // ──────────────────────────────────────────────
    #[Route('', name: 'show', methods: ['GET'])]
    public function show(int $formationId): Response
    {
        $formationObj = $this->formationRepo->find($formationId);
        $formation = $formationObj instanceof \App\Entity\Formation ? $formationObj : null;
        if (!$formation) throw $this->createNotFoundException();

        return $this->render('backoffice/exam/show.html.twig', [
            'formation' => $formation,
            'exam'      => $formation->getExam(),
        ]);
    }

    // ──────────────────────────────────────────────
    //  POST - Create the exam for this formation
    // ──────────────────────────────────────────────
    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(int $formationId, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RESPONSABLE_CLUB');
        $formationObj = $this->formationRepo->find($formationId);
        $formation = $formationObj instanceof \App\Entity\Formation ? $formationObj : null;
        if (!$formation) throw $this->createNotFoundException();

        if (!$this->isCsrfTokenValid('exam_create_'.$formationId, is_string($token = $request->request->get('_token')) ? $token : null)) {
            throw $this->createAccessDeniedException('CSRF invalide.');
        }

        if ($formation->getExam()) {
            $this->addFlash('warning', 'Un examen existe déjà pour cette formation.');
            return $this->redirectToRoute('resp_exam_show', ['formationId' => $formationId]);
        }

        $exam = new Exam();
        $exam->setTitre((string) $request->request->get('titre', 'Examen Final'));
        $exam->setDescription($request->request->get('description') ? (string) $request->request->get('description') : null);
        $exam->setPassingScore((int)$request->request->get('passing_score', 70));
        $exam->setMaxMistakes((int)$request->request->get('max_mistakes', 3));
        $exam->setFormation($formation);

        $this->em->persist($exam);
        $this->em->flush();

        $this->addFlash('success', 'Examen créé avec succès !');
        return $this->redirectToRoute('resp_exam_show', ['formationId' => $formationId]);
    }

    // ──────────────────────────────────────────────
    //  POST - Update exam settings
    // ──────────────────────────────────────────────
    #[Route('/update', name: 'update', methods: ['POST'])]
    public function update(int $formationId, Request $request): Response
    {
        $formationObj = $this->formationRepo->find($formationId);
        $formation = $formationObj instanceof \App\Entity\Formation ? $formationObj : null;
        if (!$formation || !$formation->getExam()) throw $this->createNotFoundException();

        if (!$this->isCsrfTokenValid('exam_update_'.$formationId, is_string($token = $request->request->get('_token')) ? $token : null)) {
            throw $this->createAccessDeniedException();
        }

        $exam = $formation->getExam();
        $exam->setTitre((string) $request->request->get('titre', 'Examen Final'));
        $exam->setDescription($request->request->get('description') ? (string) $request->request->get('description') : null);
        $exam->setPassingScore((int)$request->request->get('passing_score', 70));
        $exam->setMaxMistakes((int)$request->request->get('max_mistakes', 3));

        $this->em->flush();
        $this->addFlash('success', 'Paramètres mis à jour.');
        return $this->redirectToRoute('resp_exam_show', ['formationId' => $formationId]);
    }

    // ──────────────────────────────────────────────
    //  POST - Delete the exam
    // ──────────────────────────────────────────────
    #[Route('/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $formationId, Request $request): Response
    {
        $formationObj = $this->formationRepo->find($formationId);
        $formation = $formationObj instanceof \App\Entity\Formation ? $formationObj : null;
        if (!$formation || !$formation->getExam()) throw $this->createNotFoundException();

        if (!$this->isCsrfTokenValid('exam_delete_'.$formationId, is_string($token = $request->request->get('_token')) ? $token : null)) {
            throw $this->createAccessDeniedException();
        }

        $this->em->remove($formation->getExam());
        $this->em->flush();

        $this->addFlash('success', 'Examen supprimé.');
        return $this->redirectToRoute('resp_exam_show', ['formationId' => $formationId]);
    }

    // ──────────────────────────────────────────────
    //  POST - Add a question (AJAX)
    // ──────────────────────────────────────────────
    #[Route('/question/add', name: 'question_add', methods: ['POST'])]
    public function addQuestion(int $formationId, Request $request): JsonResponse
    {
        $formationObj = $this->formationRepo->find($formationId);
        $formation = $formationObj instanceof \App\Entity\Formation ? $formationObj : null;
        if (!$formation || !$formation->getExam()) {
            return new JsonResponse(['error' => 'Exam not found'], 404);
        }

        if (!$this->isCsrfTokenValid('exam_q_add_'.$formationId, is_string($token = $request->request->get('_token')) ? $token : null)) {
            return new JsonResponse(['error' => 'CSRF invalide'], 403);
        }

        $exam = $formation->getExam();
        $type = $request->request->get('type', ExamQuestion::TYPE_MCQ);

        $q = new ExamQuestion();
        $q->setExam($exam);
        $q->setQuestionText((string) $request->request->get('question_text', ''));
        $q->setType((string) $request->request->get('type', ExamQuestion::TYPE_MCQ));
        $q->setPoints((int)$request->request->get('points', 1));

        if ($type === ExamQuestion::TYPE_FILL) {
            $q->setCorrectAnswer((string) $request->request->get('correct_answer', ''));
        } elseif ($type === ExamQuestion::TYPE_MULTI_MCQ) {
            // Multiple correct answers: correct_indices[] = list of correct positions
            $choiceTexts    = $request->request->all('choice_texts');
            $correctIndexes = $request->request->all('correct_indices'); // e.g. ['0','2']
            foreach ($choiceTexts as $idx => $text) {
                $choice = new ExamChoice();
                $choice->setChoiceText(trim($text));
                $choice->setIsCorrect(in_array((string)$idx, $correctIndexes, true));
                $q->addChoice($choice);
                $this->em->persist($choice);
            }
        } elseif ($type === ExamQuestion::TYPE_DRAG_DROP) {
            // Drag-to-order: items entered in correct order
            $items = array_filter(array_map('trim', $request->request->all('drag_items')));
            $items = array_values($items);
            $q->setCorrectAnswer(json_encode($items) ?: null); // correct order stored as JSON
            foreach ($items as $item) {
                $choice = new ExamChoice();
                $choice->setChoiceText($item);
                $choice->setIsCorrect(false); // order-based grading, not a single correct flag
                $q->addChoice($choice);
                $this->em->persist($choice);
            }
        } elseif ($type === ExamQuestion::TYPE_WORD_PICK) {
            // word_pick: sentence has ____, choices are word options (one correct)
            $choiceTexts  = $request->request->all('choice_texts');
            $correctIndex = (int)$request->request->get('correct_index', 0);
            foreach ($choiceTexts as $idx => $text) {
                $choice = new ExamChoice();
                $choice->setChoiceText(trim($text));
                $choice->setIsCorrect($idx == $correctIndex);
                $q->addChoice($choice);
                $this->em->persist($choice);
            }
        } else {
            // MCQ single answer
            $choiceTexts  = $request->request->all('choice_texts');
            $correctIndex = (int)$request->request->get('correct_index', 0);
            foreach ($choiceTexts as $idx => $text) {
                $choice = new ExamChoice();
                $choice->setChoiceText(trim($text));
                $choice->setIsCorrect($idx == $correctIndex);
                $q->addChoice($choice);
                $this->em->persist($choice);
            }
        }

        $this->em->persist($q);
        $this->em->flush();

        return new JsonResponse([
            'id'            => $q->getId(),
            'question_text' => $q->getQuestionText(),
            'type'          => $q->getType(),
            'points'        => $q->getPoints(),
        ]);
    }

    // ──────────────────────────────────────────────
    //  POST - Delete a question
    // ──────────────────────────────────────────────
    #[Route('/question/{qid}/delete', name: 'question_delete', methods: ['POST'])]
    public function deleteQuestion(int $formationId, int $qid, Request $request): Response
    {
        $formationObj = $this->formationRepo->find($formationId);
        $formation = $formationObj instanceof \App\Entity\Formation ? $formationObj : null;
        if (!$formation || !$formation->getExam()) throw $this->createNotFoundException();

        if (!$this->isCsrfTokenValid('exam_q_del_'.$qid, is_string($token = $request->request->get('_token')) ? $token : null)) {
            throw $this->createAccessDeniedException();
        }

        $question = $this->em->find(ExamQuestion::class, $qid);
        if ($question && $question->getExam() === $formation->getExam()) {
            $this->em->remove($question);
            $this->em->flush();
        }

        return $this->redirectToRoute('resp_exam_show', ['formationId' => $formationId]);
    }
}
