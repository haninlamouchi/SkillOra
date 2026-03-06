<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\OptionQuestion;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Form\QuestionType;
use App\Form\QuizType;
use App\Repository\ClubRepository;
use App\Repository\FormationRepository;
use App\Repository\ParticipationFormationRepository;
use App\Repository\ResultatQuizRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admins', name: 'admin_')]
class BackofficeControllerformation extends AbstractController
{
    public function __construct(
        private FormationRepository $formationRepo,
        private ClubRepository $clubRepo,
        private UserRepository $userRepo,
        private ParticipationFormationRepository $participationRepo,
        private ResultatQuizRepository $resultatRepo,
        private EntityManagerInterface $em,
    ) {}

    // ---------------------------------------------
    // DASHBOARD
    // ---------------------------------------------

    #[Route('', name: 'dashboard')]
    public function dashboard(Request $request): Response
    {
        $totalFormations     = $this->formationRepo->count([]);
        $totalClubs          = $this->clubRepo->count([]);
        $totalUsers          = $this->userRepo->count([]);
        $totalParticipations = $this->participationRepo->count([]);

        $filters = [
            'keyword'     => $request->query->get('keyword', ''),
            'club_id'     => $request->query->get('club_id', ''),
            'responsable' => $request->query->get('responsable', ''),
            'date_from'   => $request->query->get('date_from', ''),
            'date_to'     => $request->query->get('date_to', ''),
        ];

        $formations   = $this->formationRepo->findByAdminFilters($filters);
        $clubs        = $this->clubRepo->findAll();
        $responsables = $this->userRepo->findBy(['role' => 'responsable_club']);

        return $this->render('backoffice/formation/Home.html.twig', [
            'totalFormations'     => $totalFormations,
            'totalClubs'          => $totalClubs,
            'totalUsers'          => $totalUsers,
            'totalParticipations' => $totalParticipations,
            'formations'          => $formations,
            'clubs'               => $clubs,
            'responsables'        => $responsables,
            'filters'             => $filters,
        ]);
    }

    // ---------------------------------------------
    // FORMATION SHOW (admin view � read + delete only)
    // ---------------------------------------------

    #[Route('/formation/{id}', name: 'formation_show', methods: ['GET'])]
    public function formationShow(Formation $formation): Response
    {
        $quizResults = [];
        $participantScores = [];

        foreach ($formation->getQuizzes() as $quiz) {
            $results = $this->resultatRepo->findBy(['quiz' => $quiz], ['datePassage' => 'DESC']);
            foreach ($results as $result) {
                $quizResults[] = $result;

                $user = $result->getUser();
                if (!$user) {
                    continue;
                }
                
                $uid = $user->getId();
                $pct = $result->getTotalPoints() > 0
                    ? round(($result->getScore() / $result->getTotalPoints()) * 100)
                    : 0;

                $resultQuiz = $result->getQuiz();
                if (!isset($participantScores[$uid]) || $pct > $participantScores[$uid]['pct']) {
                    $participantScores[$uid] = [
                        'score'       => $result->getScore(),
                        'totalPoints' => $result->getTotalPoints(),
                        'pct'         => $pct,
                        'quizTitre'   => $resultQuiz ? $resultQuiz->getTitre() : 'N/A',
                    ];
                }
            }
        }

        return $this->render('backoffice/formation/show.html.twig', [
            'formation'         => $formation,
            'quizResults'       => $quizResults,
            'participantScores' => $participantScores,
        ]);
    }

    // ---------------------------------------------
    // QUIZ SHOW (admin view)
    // ---------------------------------------------

    #[Route('/formation/{formationId}/quiz/{id}', name: 'quiz_show', methods: ['GET'])]
    public function quizShow(int $formationId, Quiz $quiz): Response
    {
        $formation = $this->em->getRepository(Formation::class)->find($formationId);
        if (!$formation || $quiz->getFormation() !== $formation) {
            throw $this->createNotFoundException('Quiz introuvable.');
        }

        $quizResults = $this->resultatRepo->findBy(['quiz' => $quiz], ['datePassage' => 'DESC']);

        return $this->render('backoffice/quiz/show.html.twig', [
            'formation'   => $formation,
            'quiz'        => $quiz,
            'quizResults' => $quizResults,
        ]);
    }

    // ---------------------------------------------
    // QUIZ NEW
    // ---------------------------------------------

    #[Route('/formation/{formationId}/quiz/new', name: 'quiz_new', methods: ['GET', 'POST'])]
    public function quizNew(int $formationId, Request $request): Response
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
            return $this->redirectToRoute('admin_quiz_show', [
                'formationId' => $formation->getId(),
                'id' => $quiz->getId(),
            ]);
        }

        return $this->render('backoffice/quiz/new.html.twig', [
            'formation' => $formation,
            'form' => $form,
        ]);
    }

    // ---------------------------------------------
    // QUIZ EDIT
    // ---------------------------------------------

    #[Route('/formation/{formationId}/quiz/{id}/edit', name: 'quiz_edit', methods: ['GET', 'POST'])]
    public function quizEdit(int $formationId, Quiz $quiz, Request $request): Response
    {
        $formation = $this->em->getRepository(Formation::class)->find($formationId);
        if (!$formation || $quiz->getFormation() !== $formation) {
            throw $this->createNotFoundException('Quiz introuvable.');
        }

        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Quiz modifié avec succès !');
            return $this->redirectToRoute('admin_quiz_show', [
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

    // ---------------------------------------------
    // QUIZ DELETE
    // ---------------------------------------------

    #[Route('/formation/{formationId}/quiz/{id}/delete', name: 'quiz_delete', methods: ['POST'])]
    public function quizDelete(int $formationId, Quiz $quiz, Request $request): Response
    {
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete_quiz' . $quiz->getId(), is_string($token) ? $token : null)) {
            $this->em->remove($quiz);
            $this->em->flush();
            $this->addFlash('success', 'Quiz supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_formation_show', ['id' => $formationId]);
    }

    // ---------------------------------------------
    // QUESTION NEW
    // ---------------------------------------------

    #[Route('/formation/{formationId}/quiz/{quizId}/question/new', name: 'question_new', methods: ['GET', 'POST'])]
    public function questionNew(int $formationId, int $quizId, Request $request): Response
    {
        $formation = $this->em->getRepository(Formation::class)->find($formationId);
        $quiz = $this->em->getRepository(Quiz::class)->find($quizId);
        if (!$formation || !$quiz || $quiz->getFormation() !== $formation) {
            throw $this->createNotFoundException('Quiz introuvable.');
        }

        $question = new Question();
        $question->setQuiz($quiz);

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
            $quiz->setNbQuestions($quiz->getQuestions()->count() + 1);
            $this->em->flush();

            $this->addFlash('success', 'Question ajoutée avec succès !');
            return $this->redirectToRoute('admin_quiz_show', [
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

    // ---------------------------------------------
    // QUESTION EDIT
    // ---------------------------------------------

    #[Route('/formation/{formationId}/quiz/{quizId}/question/{id}/edit', name: 'question_edit', methods: ['GET', 'POST'])]
    public function questionEdit(int $formationId, int $quizId, Question $question, Request $request): Response
    {
        $formation = $this->em->getRepository(Formation::class)->find($formationId);
        $quiz = $this->em->getRepository(Quiz::class)->find($quizId);
        if (!$formation || !$quiz || $quiz->getFormation() !== $formation || $question->getQuiz() !== $quiz) {
            throw $this->createNotFoundException('Question introuvable.');
        }

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
            return $this->redirectToRoute('admin_quiz_show', [
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

    // ---------------------------------------------
    // QUESTION DELETE
    // ---------------------------------------------

    #[Route('/formation/{formationId}/quiz/{quizId}/question/{id}/delete', name: 'question_delete', methods: ['POST'])]
    public function questionDelete(int $formationId, int $quizId, Question $question, Request $request): Response
    {
        $formation = $this->em->getRepository(Formation::class)->find($formationId);
        $quiz = $this->em->getRepository(Quiz::class)->find($quizId);
        if (!$formation || !$quiz) {
            throw $this->createNotFoundException('Quiz introuvable.');
        }

        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete_question' . $question->getId(), is_string($token) ? $token : null)) {
            $quiz->removeQuestion($question);
            $this->em->remove($question);
            $quiz->setNbQuestions(max(0, $quiz->getQuestions()->count()));
            $this->em->flush();
            $this->addFlash('success', 'Question supprimée.');
        }

        return $this->redirectToRoute('admin_quiz_show', [
            'formationId' => $formation->getId(),
            'id' => $quiz->getId(),
        ]);
    }

    // ---------------------------------------------
    // FORMATION DELETE
    // ---------------------------------------------

    #[Route('/formation/{id}/delete', name: 'formation_delete', methods: ['POST'])]
    public function formationDelete(Request $request, Formation $formation): Response
    {
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $formation->getId(), is_string($token) ? $token : null)) {
            $this->em->remove($formation);
            $this->em->flush();
            $this->addFlash('success', 'Formation supprim�e avec succ�s.');
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    // ---------------------------------------------
    // INDEX (legacy)
    // ---------------------------------------------

    #[Route('/index', name: 'index')]
    public function index(): Response
    {
        return $this->render('backoffice/index.html.twig');
    }
}
