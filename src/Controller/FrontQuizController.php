<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\Quiz;
use App\Entity\ResultatQuiz;
use App\Repository\ParticipationFormationRepository;
use App\Repository\ResultatQuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/formations/{formationId}/quiz', name: 'front_quiz_')]
class FrontQuizController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ResultatQuizRepository $resultatRepo,
    ) {}

    // ──────────────────────────────────────────────
    //  GATEWAY: /formations/{formationId}/quiz
    //  Checks auth + participation, then redirects
    // ──────────────────────────────────────────────

    #[Route('', name: 'gateway', methods: ['GET'])]
    public function gateway(int $formationId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $formation = $this->em->getRepository(Formation::class)->find($formationId);
        if (!$formation) {
            throw $this->createNotFoundException('Formation introuvable.');
        }

        // Check if formation has quizzes
        $quizzes = $formation->getQuizzes();
        if ($quizzes->isEmpty()) {
            $this->addFlash('info', 'Aucun quiz n\'est disponible pour cette formation.');
            return $this->redirectToRoute('front_formation_show', ['id' => $formation->getId()]);
        }

        // Redirect to first quiz
        $firstQuiz = $quizzes->first();
        if (!$firstQuiz) {
            $this->addFlash('error', 'Aucun quiz disponible.');
            return $this->redirectToRoute('front_formation_show', ['id' => $formation->getId()]);
        }
        return $this->redirectToRoute('front_quiz_start', [
            'formationId' => $formation->getId(),
            'id' => $firstQuiz->getId(),
        ]);
    }

    // ──────────────────────────────────────────────
    //  START / DISPLAY QUIZ
    // ──────────────────────────────────────────────

    #[Route('/{id}', name: 'start', methods: ['GET'])]
    public function start(int $formationId, Quiz $quiz): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $formation = $this->em->getRepository(Formation::class)->find($formationId);
        if (!$formation || $quiz->getFormation() !== $formation) {
            throw $this->createNotFoundException('Quiz introuvable pour cette formation.');
        }

        // Build correct answers map for W3Schools-style immediate feedback
        $correctAnswers = [];
        foreach ($quiz->getQuestions() as $question) {
            foreach ($question->getOptionQuestions() as $option) {
                if ($option->isEstCorrect()) {
                    $correctAnswers[$question->getId()] = $option->getId();
                    break;
                }
            }
        }

        return $this->render('frontoffice/quiz/start.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
            'correctAnswers' => $correctAnswers,
        ]);
    }

    // ──────────────────────────────────────────────
    //  SUBMIT QUIZ ANSWERS
    // ──────────────────────────────────────────────

    #[Route('/{id}/submit', name: 'submit', methods: ['POST'])]
    public function submit(Request $request, int $formationId, Quiz $quiz): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $formation = $this->em->getRepository(Formation::class)->find($formationId);
        if (!$formation || $quiz->getFormation() !== $formation) {
            throw $this->createNotFoundException('Quiz introuvable pour cette formation.');
        }

        // CSRF check
        if (!$this->isCsrfTokenValid('quiz_submit' . $quiz->getId(), is_string($token = $request->request->get('_token')) ? $token : null)) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('front_quiz_start', [
                'formationId' => $formation->getId(),
                'id' => $quiz->getId(),
            ]);
        }

        // Allow retaking: delete previous result if any
        $existingResult = $this->resultatRepo->findOneBy([
            'user' => $this->getUser(),
            'quiz' => $quiz,
        ]);
        if ($existingResult) {
            $this->em->remove($existingResult);
        }

        // Calculate score
        $score = 0;
        $totalPoints = 0;
        $answers = $request->request->all('answers'); // ['questionId' => 'optionId', ...]

        foreach ($quiz->getQuestions() as $question) {
            $points = $question->getPoints() ?? 1;
            $totalPoints += $points;

            $selectedOptionId = $answers[$question->getId()] ?? null;

            if ($selectedOptionId) {
                foreach ($question->getOptionQuestions() as $option) {
                    if ($option->getId() == $selectedOptionId && $option->isEstCorrect()) {
                        $score += $points;
                        break;
                    }
                }
            }
        }

        // Save result
        $resultat = new ResultatQuiz();
        $resultat->setQuiz($quiz);
        $currentUser = $this->getUser();
        $resultat->setUser($currentUser instanceof \App\Entity\User ? $currentUser : null);
        $resultat->setScore($score);
        $resultat->setTotalPoints($totalPoints);
        $resultat->setReponses($answers); // store user answers for result review

        $this->em->persist($resultat);
        $this->em->flush();

        return $this->redirectToRoute('front_quiz_result', [
            'formationId' => $formation->getId(),
            'id'          => $quiz->getId(),
        ]);
    }

    // ──────────────────────────────────────────────
    //  SHOW QUIZ RESULT
    // ──────────────────────────────────────────────

    #[Route('/{id}/result', name: 'result', methods: ['GET'])]
    public function result(int $formationId, Quiz $quiz): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $formation = $this->em->getRepository(Formation::class)->find($formationId);
        if (!$formation || $quiz->getFormation() !== $formation) {
            throw $this->createNotFoundException('Quiz introuvable pour cette formation.');
        }

        $resultat = $this->resultatRepo->findOneBy([
            'user' => $this->getUser(),
            'quiz' => $quiz,
        ]);

        if (!$resultat) {
            $this->addFlash('warning', 'Vous n\'avez pas encore passé ce quiz.');
            return $this->redirectToRoute('front_quiz_start', [
                'formationId' => $formation->getId(),
                'id' => $quiz->getId(),
            ]);
        }

        // Build a map of correct answers for display
        $correctAnswers = [];
        foreach ($quiz->getQuestions() as $question) {
            foreach ($question->getOptionQuestions() as $option) {
                if ($option->isEstCorrect()) {
                    $correctAnswers[$question->getId()] = $option->getId();
                    break;
                }
            }
        }

        // Level navigation + aggregate stats
        $nextQuiz   = null;
        $quizIndex  = 0;   // 0-based position in level
        $levelStats = null;

        $level = $quiz->getLevel();
        if ($level) {
            $levelQuizzes = $level->getQuizzes()->toArray();
            usort($levelQuizzes, fn($a, $b) => $a->getId() <=> $b->getId());

            $foundCurrent = false;
            foreach ($levelQuizzes as $i => $lq) {
                if ($lq->getId() === $quiz->getId()) {
                    $quizIndex    = $i;
                    $foundCurrent = true;
                } elseif ($foundCurrent && $nextQuiz === null) {
                    $nextQuiz = $lq;
                }
            }

            // Aggregate: sum scores of all quizzes that have a result
            $totalScore = 0;
            $totalMax   = 0;
            $allDone    = true;
            foreach ($levelQuizzes as $lq) {
                $r = $this->resultatRepo->findOneBy(['user' => $this->getUser(), 'quiz' => $lq]);
                if ($r) {
                    $totalScore += $r->getScore();
                    $totalMax   += $r->getTotalPoints();
                } else {
                    $allDone = false;
                }
            }
            $levelPct = ($allDone && $totalMax > 0)
                ? (int) round($totalScore / $totalMax * 100)
                : null;

            $levelStats = [
                'titre'       => $level->getTitre(),
                'total'       => count($levelQuizzes),
                'allDone'     => $allDone,
                'totalScore'  => $totalScore,
                'totalMax'    => $totalMax,
                'pct'         => $levelPct,
                'passed'      => $levelPct !== null && $levelPct >= 50,
            ];
        }

        return $this->render('frontoffice/quiz/result.html.twig', [
            'formation'    => $formation,
            'quiz'         => $quiz,
            'resultat'     => $resultat,
            'correctAnswers' => $correctAnswers,
            'userAnswers'  => $resultat->getReponses() ?? [],
            'nextQuiz'     => $nextQuiz,
            'quizIndex'    => $quizIndex,
            'levelStats'   => $levelStats,
        ]);
    }
}
