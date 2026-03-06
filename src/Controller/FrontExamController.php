<?php

namespace App\Controller;

use App\Entity\ExamAttempt;
use App\Repository\ExamAttemptRepository;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/formations/{formationId}/exam', name: 'front_exam_')]
#[IsGranted('ROLE_MEMBRE')]
class FrontExamController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private FormationRepository $formationRepo,
        private ExamAttemptRepository $attemptRepo,
    ) {}

    // ──────────────────────────────────────────────
    //  GET - Exam intro page
    // ──────────────────────────────────────────────
    #[Route('', name: 'intro', methods: ['GET'])]
    public function intro(int $formationId): Response
    {
        $formationObj = $this->formationRepo->find($formationId);
        $formation = $formationObj instanceof \App\Entity\Formation ? $formationObj : null;
        if (!$formation || !$formation->getExam()) throw $this->createNotFoundException();

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $exam = $formation->getExam();
        $lastAttempt = $this->attemptRepo->findLatestForUser($exam, $user);

        return $this->render('frontoffice/exam/intro.html.twig', [
            'formation'   => $formation,
            'exam'        => $exam,
            'lastAttempt' => $lastAttempt,
        ]);
    }

    // ──────────────────────────────────────────────
    //  POST - Start / restart the exam
    // ──────────────────────────────────────────────
    #[Route('/start', name: 'start', methods: ['POST'])]
    public function start(int $formationId, Request $request): Response
    {
        $formationObj = $this->formationRepo->find($formationId);
        $formation = $formationObj instanceof \App\Entity\Formation ? $formationObj : null;
        if (!$formation || !$formation->getExam()) throw $this->createNotFoundException();

        if (!$this->isCsrfTokenValid('exam_start_'.$formationId, is_string($token = $request->request->get('_token')) ? $token : null)) {
            throw $this->createAccessDeniedException();
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $exam = $formation->getExam();

        // Randomize question order
        $questions   = $exam->getQuestions()->toArray();
        $questionIds = array_map(fn($q) => $q->getId(), $questions);
        shuffle($questionIds);

        $totalPoints = array_sum(array_map(fn($q) => $q->getPoints(), $questions));

        $attempt = new ExamAttempt();
        $attempt->setExam($exam);
        $attempt->setUser($user);
        $attempt->setQuestionOrder(json_encode($questionIds) ?: '[]');
        $attempt->setTotalPoints($totalPoints);

        $this->em->persist($attempt);
        $this->em->flush();

        return $this->redirectToRoute('front_exam_take', [
            'formationId' => $formationId,
            'attemptId'   => $attempt->getId(),
        ]);
    }

    // ──────────────────────────────────────────────
    //  GET - Take the exam (interactive page)
    // ──────────────────────────────────────────────
    #[Route('/take/{attemptId}', name: 'take', methods: ['GET'])]
    public function take(int $formationId, int $attemptId): Response
    {
        $formationObj = $this->formationRepo->find($formationId);
        $formation = $formationObj instanceof \App\Entity\Formation ? $formationObj : null;
        if (!$formation || !$formation->getExam()) throw $this->createNotFoundException();

        $attempt = $this->em->find(ExamAttempt::class, $attemptId);
        if (!$attempt || $attempt->getExam() !== $formation->getExam()) throw $this->createNotFoundException();

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if ($attempt->getUser() !== $user) throw $this->createAccessDeniedException();

        if ($attempt->getStatus() !== 'in_progress') {
            return $this->redirectToRoute('front_exam_result', [
                'formationId' => $formationId,
                'attemptId'   => $attemptId,
            ]);
        }

        $exam        = $formation->getExam();
        $questionIds = json_decode($attempt->getQuestionOrder(), true);

        // Build ordered question data with shuffled choices
        $questionsData = [];
        foreach ($questionIds as $qid) {
            foreach ($exam->getQuestions() as $q) {
                if ($q->getId() === $qid) {
                    $choices = $q->getChoices()->toArray();
                    shuffle($choices);

                    // Compute correct data per type (embedded for immediate client feedback)
                    $correctAnswer   = null;
                    $correctChoiceId = null;
                    $correctChoiceIds = null;

                    switch ($q->getType()) {
                        case 'fill':
                            $correctAnswer = $q->getCorrectAnswer();
                            break;
                        case 'drag_drop':
                            // correctAnswer holds JSON array; send shuffled item texts
                            // Do NOT reveal order client-side; validate server-side only
                            $correctAnswer = null;
                            break;
                        case 'mcq':
                        case 'word_pick':
                            $cc = $q->getCorrectChoice();
                            $correctChoiceId = $cc ? $cc->getId() : null;
                            break;
                        case 'multi_mcq':
                            $correctChoiceIds = array_values(array_map(
                                fn($c) => $c->getId(),
                                array_filter($q->getChoices()->toArray(), fn($c) => $c->isCorrect())
                            ));
                            break;
                    }

                    $questionsData[] = [
                        'id'               => $q->getId(),
                        'text'             => $q->getQuestionText(),
                        'type'             => $q->getType(),
                        'points'           => $q->getPoints(),
                        'correct_answer'   => $correctAnswer,
                        'correct_choice_id' => $correctChoiceId,
                        'correct_choice_ids'=> $correctChoiceIds,
                        'choices'          => array_map(fn($c) => [
                            'id'   => $c->getId(),
                            'text' => $c->getChoiceText(),
                        ], $choices),
                    ];
                    break;
                }
            }
        }

        return $this->render('frontoffice/exam/take.html.twig', [
            'formation'     => $formation,
            'exam'          => $exam,
            'attempt'       => $attempt,
            'questionsData' => $questionsData,
            'maxMistakes'   => $exam->getMaxMistakes(),
        ]);
    }

    // ──────────────────────────────────────────────
    //  POST - Submit the full exam answers at once
    // ──────────────────────────────────────────────
    #[Route('/submit/{attemptId}', name: 'submit', methods: ['POST'])]
    public function submit(int $formationId, int $attemptId, Request $request): JsonResponse
    {
        $formationObj = $this->formationRepo->find($formationId);
        $formation = $formationObj instanceof \App\Entity\Formation ? $formationObj : null;
        if (!$formation || !$formation->getExam()) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $attempt = $this->em->find(ExamAttempt::class, $attemptId);
        if (!$attempt || $attempt->getExam() !== $formation->getExam()) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if ($attempt->getUser() !== $user) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }

        if ($attempt->getStatus() !== 'in_progress') {
            return new JsonResponse(['redirect' => $this->generateUrl('front_exam_result', [
                'formationId' => $formationId,
                'attemptId'   => $attemptId,
            ])]);
        }

        $exam        = $formation->getExam();
        $data        = json_decode($request->getContent(), true) ?? [];
        $userAnswers = $data['answers'] ?? []; // [questionId => answer]
        $forceStopped= $data['force_stopped'] ?? false;

        $questionIds = json_decode($attempt->getQuestionOrder(), true);
        $score       = 0;
        $mistakes    = 0;
        $answersMap  = [];

        foreach ($questionIds as $qid) {
            // Find the question
            $question = null;
            foreach ($exam->getQuestions() as $q) {
                if ($q->getId() === $qid) { $question = $q; break; }
            }
            if (!$question) continue;

            $given     = $userAnswers[(string)$qid] ?? null;
            $isCorrect = false;

            if ($given !== null) {
                $type = $question->getType();
                if ($type === 'fill') {
                    $isCorrect = strtolower(trim((string)$given)) === strtolower(trim($question->getCorrectAnswer() ?? ''));
                } elseif ($type === 'multi_mcq') {
                    // given is array of selected choice IDs
                    $correctIds = array_map(fn($c) => $c->getId(),
                        array_filter($question->getChoices()->toArray(), fn($c) => $c->isCorrect()));
                    $givenIds = is_array($given) ? array_map('intval', $given) : [];
                    sort($correctIds); sort($givenIds);
                    $isCorrect = $correctIds === $givenIds;
                } elseif ($type === 'drag_drop') {
                    // given is ordered array of item texts
                    $correctOrder = json_decode($question->getCorrectAnswer() ?? '[]', true);
                    $givenOrder   = is_array($given) ? $given : (is_string($given) ? json_decode($given, true) : []);
                    $isCorrect    = $correctOrder === $givenOrder;
                } elseif ($type === 'word_pick') {
                    // word_pick: given is choice id (int), same grading as mcq
                    $correctChoice = $question->getCorrectChoice();
                    $isCorrect = $correctChoice && (int)$given === $correctChoice->getId();
                } else {
                    // MCQ: given is choice id (int)
                    $correctChoice = $question->getCorrectChoice();
                    $isCorrect = $correctChoice && (int)$given === $correctChoice->getId();
                }
            }

            if ($isCorrect) {
                $score += $question->getPoints();
            } else {
                $mistakes++;
            }

            $answersMap[$qid] = ['given' => $given, 'correct' => $isCorrect];
        }

        $percentage = $attempt->getTotalPoints() > 0
            ? (int) round(($score / $attempt->getTotalPoints()) * 100)
            : 0;

        $status = 'failed';
        if ($forceStopped) {
            $status = 'stopped';
        } elseif ($percentage >= $exam->getPassingScore()) {
            $status = 'passed';
        }

        $attempt->setScore($score);
        $attempt->setMistakeCount($mistakes);
        $attempt->setStatus($status);
        $attempt->setAnswers(json_encode($answersMap) ?: '{}');
        $attempt->setCompletedAt(new \DateTime());

        $this->em->flush();

        return new JsonResponse([
            'status'     => $status,
            'score'      => $score,
            'total'      => $attempt->getTotalPoints(),
            'percentage' => $percentage,
            'mistakes'   => $mistakes,
            'redirect'   => $this->generateUrl('front_exam_result', [
                'formationId' => $formationId,
                'attemptId'   => $attemptId,
            ]),
        ]);
    }

    // ──────────────────────────────────────────────
    //  GET - Result page
    // ──────────────────────────────────────────────
    #[Route('/result/{attemptId}', name: 'result', methods: ['GET'])]
    public function result(int $formationId, int $attemptId): Response
    {
        $formationObj = $this->formationRepo->find($formationId);
        $formation = $formationObj instanceof \App\Entity\Formation ? $formationObj : null;
        if (!$formation || !$formation->getExam()) throw $this->createNotFoundException();

        $attempt = $this->em->find(ExamAttempt::class, $attemptId);
        if (!$attempt) throw $this->createNotFoundException();

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if ($attempt->getUser() !== $user) throw $this->createAccessDeniedException();

        $exam        = $formation->getExam();
        $answersMap  = json_decode($attempt->getAnswers(), true) ?? [];
        $questionIds = json_decode($attempt->getQuestionOrder(), true) ?? [];

        $details = [];
        foreach ($questionIds as $qid) {
            foreach ($exam->getQuestions() as $q) {
                if ($q->getId() === $qid) {
                    $info = $answersMap[(string)$qid] ?? [];
                    $correct = '—';
                    switch ($q->getType()) {
                        case 'mcq':
                        case 'word_pick':
                            $cc = $q->getCorrectChoice();
                            $correct = $cc ? $cc->getChoiceText() : '—';
                            break;
                        case 'multi_mcq':
                            $correctTexts = array_map(
                                fn($c) => $c->getChoiceText(),
                                array_filter($q->getChoices()->toArray(), fn($c) => $c->isCorrect())
                            );
                            $correct = implode(', ', $correctTexts);
                            break;
                        case 'drag_drop':
                            $items = json_decode($q->getCorrectAnswer() ?? '[]', true);
                            $correct = implode(' → ', $items ?: []);
                            break;
                        default: // fill
                            $correct = $q->getCorrectAnswer() ?? '—';
                    }
                    $details[] = [
                        'question'   => $q->getQuestionText(),
                        'type'       => $q->getType(),
                        'given'      => $info['given'] ?? null,
                        'isCorrect'  => $info['correct'] ?? false,
                        'correct'    => $correct,
                        'choices'    => $q->getChoices()->toArray(),
                        'points'     => $q->getPoints(),
                    ];
                    break;
                }
            }
        }

        return $this->render('frontoffice/exam/result.html.twig', [
            'formation'  => $formation,
            'exam'       => $exam,
            'attempt'    => $attempt,
            'details'    => $details,
            'percentage' => $attempt->getPercentage(),
        ]);
    }

    // ──────────────────────────────────────────────
    //  GET - Certificate page
    // ──────────────────────────────────────────────
    #[Route('/certificate/{attemptId}', name: 'certificate', methods: ['GET'])]
    public function certificate(int $formationId, int $attemptId): Response
    {
        $formationObj = $this->formationRepo->find($formationId);
        $formation = $formationObj instanceof \App\Entity\Formation ? $formationObj : null;
        if (!$formation || !$formation->getExam()) throw $this->createNotFoundException();

        $attempt = $this->em->find(ExamAttempt::class, $attemptId);
        if (!$attempt) throw $this->createNotFoundException();

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if ($attempt->getUser() !== $user) throw $this->createAccessDeniedException();

        $percentage = $attempt->getPercentage();
        if ($percentage < 60) {
            throw $this->createAccessDeniedException('Score insuffisant pour obtenir un certificat.');
        }

        return $this->render('frontoffice/exam/certificate.html.twig', [
            'formation'  => $formation,
            'exam'       => $formation->getExam(),
            'attempt'    => $attempt,
            'user'       => $user,
            'percentage' => $percentage,
        ]);
    }

    // ──────────────────────────────────────────────
    //  POST - Send verification code for certificate download
    // ──────────────────────────────────────────────
    #[Route('/certificate/{attemptId}/send-code', name: 'certificate_send_code', methods: ['POST'])]
    public function sendCertificateCode(
        int $formationId,
        int $attemptId,
        Request $request,
        MailerInterface $mailer,
    ): JsonResponse {
        $formation = $this->formationRepo->find($formationId);
        if (!$formation instanceof \App\Entity\Formation) {
            return new JsonResponse(['success' => false, 'message' => 'Formation introuvable.'], 404);
        }

        $attempt = $this->em->find(ExamAttempt::class, $attemptId);
        if (!$attempt) return new JsonResponse(['success' => false, 'message' => 'Tentative introuvable.'], 404);

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if ($attempt->getUser() !== $user) {
            return new JsonResponse(['success' => false, 'message' => 'Accès refusé.'], 403);
        }

        $code = (string) random_int(100000, 999999);
        $session = $request->getSession();
        $session->set('cert_code_' . $attemptId, $code);
        $session->set('cert_code_expires_' . $attemptId, time() + 600); // 10 min

        $recipientEmail = $user->getEmail();
        if (!$recipientEmail) {
            return new JsonResponse(['success' => false, 'message' => 'Email introuvable pour cet utilisateur.'], 400);
        }

        $email = (new Email())
            ->from('noreply@skillora.com')
            ->to($recipientEmail)
            ->subject('Code de vérification — Certificat SkillOra')
            ->html(
                '<div style="font-family:DM Sans,sans-serif;max-width:480px;margin:0 auto;padding:32px 24px;background:#fff;border-radius:16px;border:1px solid #e5e7eb;">'
                . '<h2 style="color:#b8860b;font-size:1.4rem;margin:0 0 8px;">🎓 Votre certificat SkillOra</h2>'
                . '<p style="color:#374151;font-size:0.92rem;line-height:1.6;margin:0 0 24px;">Utilisez le code ci-dessous pour télécharger votre certificat pour la formation <strong>' . htmlspecialchars($formation->getTitre() ?? '') . '</strong>.</p>'
                . '<div style="background:#fffbeb;border:2px solid #b8860b;border-radius:12px;padding:20px;text-align:center;margin-bottom:24px;">'
                . '<div style="font-size:2.2rem;font-weight:800;letter-spacing:.3em;color:#92400e;">' . $code . '</div>'
                . '<div style="font-size:0.78rem;color:#9a7a30;margin-top:6px;">Valide 10 minutes</div>'
                . '</div>'
                . '<p style="color:#9ca3af;font-size:0.78rem;margin:0;">Si vous n\'avez pas demandé ce code, ignorez cet email.</p>'
                . '</div>'
            );

        $mailer->send($email);

        return new JsonResponse(['success' => true, 'email' => $recipientEmail]);
    }

    // ──────────────────────────────────────────────
    //  POST - Verify code and allow PDF download
    // ──────────────────────────────────────────────
    #[Route('/certificate/{attemptId}/verify-code', name: 'certificate_verify_code', methods: ['POST'])]
    public function verifyCertificateCode(
        int $attemptId,
        Request $request,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $inputCode = trim($data['code'] ?? '');

        $session = $request->getSession();
        $storedCode    = $session->get('cert_code_' . $attemptId);
        $expiresAt     = $session->get('cert_code_expires_' . $attemptId);

        if (!$storedCode) {
            return new JsonResponse(['success' => false, 'message' => 'Aucun code envoyé. Veuillez d\'abord demander un code.']);
        }
        if (time() > $expiresAt) {
            $session->remove('cert_code_' . $attemptId);
            $session->remove('cert_code_expires_' . $attemptId);
            return new JsonResponse(['success' => false, 'message' => 'Code expiré. Veuillez en demander un nouveau.']);
        }
        if ($inputCode !== $storedCode) {
            return new JsonResponse(['success' => false, 'message' => 'Code incorrect. Vérifiez votre email et réessayez.']);
        }

        $session->remove('cert_code_' . $attemptId);
        $session->remove('cert_code_expires_' . $attemptId);

        return new JsonResponse(['success' => true]);
    }
}
