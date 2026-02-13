<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\Quiz;
use App\Entity\ResultatQuiz;
use App\Entity\User;
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
        private ParticipationFormationRepository $participationRepo,
        private ResultatQuizRepository $resultatRepo,
    ) {}

    private function getDefaultUser(): User
    {
        $user = $this->em->getRepository(User::class)->find(123);
        if (!$user) {
            $user = $this->em->getRepository(User::class)->findOneBy([]);
        }
        if (!$user) {
            throw new \RuntimeException('Aucun utilisateur trouvé en base.');
        }
        return $user;
    }

    // ──────────────────────────────────────────────
    //  GATEWAY: /formations/{formationId}/quiz
    //  Checks auth + participation, then redirects
    // ──────────────────────────────────────────────

    #[Route('', name: 'gateway', methods: ['GET'])]
    public function gateway(int $formationId): Response
    {
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
        $formation = $this->em->getRepository(Formation::class)->find($formationId);
        if (!$formation || $quiz->getFormation() !== $formation) {
            throw $this->createNotFoundException('Quiz introuvable pour cette formation.');
        }

        // Check if already passed this quiz
        $existingResult = $this->resultatRepo->findOneBy([
            'user' => $this->getDefaultUser(),
            'quiz' => $quiz,
        ]);

        if ($existingResult) {
            return $this->redirectToRoute('front_quiz_result', [
                'formationId' => $formation->getId(),
                'id' => $quiz->getId(),
            ]);
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
        $formation = $this->em->getRepository(Formation::class)->find($formationId);
        if (!$formation || $quiz->getFormation() !== $formation) {
            throw $this->createNotFoundException('Quiz introuvable pour cette formation.');
        }

        // CSRF check
        if (!$this->isCsrfTokenValid('quiz_submit' . $quiz->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('front_quiz_start', [
                'formationId' => $formation->getId(),
                'id' => $quiz->getId(),
            ]);
        }

        // Prevent double submission
        $existingResult = $this->resultatRepo->findOneBy([
            'user' => $this->getDefaultUser(),
            'quiz' => $quiz,
        ]);

        if ($existingResult) {
            $this->addFlash('info', 'Vous avez déjà passé ce quiz.');
            return $this->redirectToRoute('front_quiz_result', [
                'formationId' => $formation->getId(),
                'id' => $quiz->getId(),
            ]);
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
        $resultat->setUser($this->getDefaultUser());
        $resultat->setScore($score);
        $resultat->setTotalPoints($totalPoints);
        $resultat->setReponses($answers); // store user answers for result review

        $this->em->persist($resultat);
        $this->em->flush();

        return $this->redirectToRoute('front_quiz_result', [
            'formationId' => $formation->getId(),
            'id' => $quiz->getId(),
        ]);
    }

    // ──────────────────────────────────────────────
    //  SHOW QUIZ RESULT
    // ──────────────────────────────────────────────

    #[Route('/{id}/result', name: 'result', methods: ['GET'])]
    public function result(int $formationId, Quiz $quiz): Response
    {
        $formation = $this->em->getRepository(Formation::class)->find($formationId);
        if (!$formation || $quiz->getFormation() !== $formation) {
            throw $this->createNotFoundException('Quiz introuvable pour cette formation.');
        }

        $resultat = $this->resultatRepo->findOneBy([
            'user' => $this->getDefaultUser(),
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

        return $this->render('frontoffice/quiz/result.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
            'resultat' => $resultat,
            'correctAnswers' => $correctAnswers,
            'userAnswers' => $resultat->getReponses() ?? [],
        ]);
    }
}
