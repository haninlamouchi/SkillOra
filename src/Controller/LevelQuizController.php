<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\Level;
use App\Entity\OptionQuestion;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Repository\FormationRepository;
use App\Repository\LevelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/formation/{formationId}/levels')]
final class LevelQuizController extends AbstractController
{
    public const QUIZ_TYPES = [
        'QCM classique'       => 'quiz',
        'Associer des paires' => 'match_pairs',
        "Trouver l'erreur"    => 'find_error',
        'Remplir les blancs'  => 'fill_blank',
    ];

    private const VALID_TYPES = ['quiz', 'match_pairs', 'find_error', 'fill_blank'];

    public function __construct(
        private EntityManagerInterface $em,
        private LevelRepository        $levelRepo,
    ) {}

    // ──────────────────────────────────────────────
    //  LIST LEVELS FOR A FORMATION
    // ──────────────────────────────────────────────

    #[Route('', name: 'app_level_index', methods: ['GET'])]
    public function index(int $formationId): Response
    {
        $formation = $this->getFormationOr404($formationId);

        return $this->render('frontoffice/responsableformation/level/index.html.twig', [
            'formation' => $formation,
            'levels'    => $this->levelRepo->findByFormation($formationId),
        ]);
    }

    // ──────────────────────────────────────────────
    //  CREATE A NEW LEVEL
    // ──────────────────────────────────────────────

    #[Route('/new', name: 'app_level_new', methods: ['GET', 'POST'])]
    public function new(int $formationId, Request $request): Response
    {
        $formation = $this->getFormationOr404($formationId);

        if ($request->isMethod('POST')) {
            $titre       = trim((string) $request->request->get('titre', ''));
            $numero      = (int) $request->request->get('numero', 1);
            $description = trim((string) $request->request->get('description', ''));
            $scoreReq    = $request->request->get('scoreRequired') !== '' ? (int) $request->request->get('scoreRequired') : null;

            if ($titre === '') {
                $this->addFlash('danger', 'Le titre est obligatoire.');
                return $this->redirectToRoute('app_level_new', ['formationId' => $formationId]);
            }

            $level = new Level();
            $level->setFormation($formation);
            $level->setNumero($numero);
            $level->setTitre($titre);
            $level->setDescription($description ?: null);
            $level->setScoreRequired($scoreReq);

            $this->em->persist($level);
            $this->em->flush();

            $this->addFlash('success', 'Niveau créé avec succès.');
            return $this->redirectToRoute('app_level_index', ['formationId' => $formationId]);
        }

        $nextNumero = count($this->levelRepo->findByFormation($formationId)) + 1;

        return $this->render('frontoffice/responsableformation/level/new.html.twig', [
            'formation'   => $formation,
            'nextNumero'  => $nextNumero,
        ]);
    }

    // ──────────────────────────────────────────────
    //  SHOW LEVEL DETAIL + ITS QUIZZES
    // ──────────────────────────────────────────────

    #[Route('/{id}', name: 'app_level_show', methods: ['GET'])]
    public function show(int $formationId, int $id): Response
    {
        $formation = $this->getFormationOr404($formationId);
        $level     = $this->getLevelOr404($id, $formation);

        return $this->render('frontoffice/responsableformation/level/show.html.twig', [
            'formation'  => $formation,
            'level'      => $level,
            'quizzes'    => $level->getQuizzes(),
            'quiz_types' => self::QUIZ_TYPES,
        ]);
    }

    // ──────────────────────────────────────────────
    //  CREATE QUIZ INSIDE A LEVEL
    // ──────────────────────────────────────────────

    #[Route('/{id}/quiz/new', name: 'app_level_quiz_new', methods: ['GET', 'POST'])]
    public function newQuiz(int $formationId, int $id, Request $request): Response
    {
        $formation = $this->getFormationOr404($formationId);
        $level     = $this->getLevelOr404($id, $formation);

        if ($request->isMethod('POST')) {
            $titre       = trim((string) $request->request->get('titre', ''));
            $description = trim((string) $request->request->get('description', ''));
            $type        = $request->request->get('type', 'quiz');
            $dureeRaw    = $request->request->get('duree', '');
            $duree       = ($dureeRaw !== '' && $dureeRaw !== null) ? (int) $dureeRaw : null;

            if ($titre === '') {
                $this->addFlash('danger', 'Le titre du quiz est obligatoire.');
                return $this->redirectToRoute('app_level_quiz_new', [
                    'formationId' => $formationId,
                    'id'          => $id,
                ]);
            }

            if (!in_array($type, self::VALID_TYPES, true)) {
                $type = 'quiz';
            }

            $quiz = new Quiz();
            $quiz->setFormation($formation);
            $quiz->setLevel($level);
            $quiz->setTitre($titre);
            $quiz->setDescription($description ?: 'Aucune description.');
            $quiz->setType($type);
            $quiz->setDuree($duree);

            $this->em->persist($quiz);

            // ── Process questions ──────────────────────────────────────
            $questionsData = $request->request->all('questions');
            $qCount        = 0;

            foreach ($questionsData as $qData) {
                $contenu = trim($qData['contenu'] ?? '');
                if ($contenu === '') {
                    continue;
                }

                $question = new Question();
                $question->setQuiz($quiz);
                $question->setContenu($contenu);
                $question->setType($type === 'quiz' ? 'multiple_choice' : $type);
                $question->setPoints(
                    isset($qData['points']) && $qData['points'] !== '' ? (int) $qData['points'] : 1
                );

                switch ($type) {
                    case 'quiz':
                        $ordre = 0;
                        foreach ($qData['options'] ?? [] as $optData) {
                            $text = trim($optData['contenu'] ?? '');
                            if ($text === '') {
                                continue;
                            }
                            $opt = new OptionQuestion();
                            $opt->setContenu($text);
                            $opt->setEstCorrect(!empty($optData['correct']));
                            $opt->setOrdre(++$ordre);
                            $question->addOptionQuestion($opt);
                            $this->em->persist($opt);
                        }
                        break;

                    case 'match_pairs':
                        foreach ($qData['pairs'] ?? [] as $pair) {
                            $left  = trim($pair['left']  ?? '');
                            $right = trim($pair['right'] ?? '');
                            if ($left === '' || $right === '') {
                                continue;
                            }
                            $opt = new OptionQuestion();
                            $opt->setContenu($right);
                            $opt->setPairKey($left);
                            $opt->setEstCorrect(true);
                            $question->addOptionQuestion($opt);
                            $this->em->persist($opt);
                        }
                        break;

                    case 'find_error':
                        $wrongPart   = trim($qData['wrong_part']  ?? '');
                        $explication = trim($qData['explication'] ?? '');
                        if ($wrongPart !== '') {
                            $opt = new OptionQuestion();
                            $opt->setContenu($wrongPart);
                            $opt->setPairKey('wrong');
                            $opt->setEstCorrect(true);
                            $question->addOptionQuestion($opt);
                            $this->em->persist($opt);
                        }
                        if ($explication !== '') {
                            $opt = new OptionQuestion();
                            $opt->setContenu($explication);
                            $opt->setPairKey('explanation');
                            $opt->setEstCorrect(false);
                            $question->addOptionQuestion($opt);
                            $this->em->persist($opt);
                        }
                        break;

                    case 'fill_blank':
                        foreach ($qData['blanks'] ?? [] as $pos => $blank) {
                            $blank = trim($blank);
                            if ($blank === '') {
                                continue;
                            }
                            $opt = new OptionQuestion();
                            $opt->setContenu($blank);
                            $opt->setOrdre((int) $pos + 1);
                            $opt->setEstCorrect(true);
                            $question->addOptionQuestion($opt);
                            $this->em->persist($opt);
                        }
                        break;
                }

                $this->em->persist($question);
                ++$qCount;
            }

            $quiz->setNbQuestions($qCount);
            $this->em->flush();

            $this->addFlash('success', "Quiz \"{$quiz->getTitre()}\" créé avec {$qCount} question(s).");
            return $this->redirectToRoute('app_level_show', [
                'formationId' => $formationId,
                'id'          => $id,
            ]);
        }

        return $this->render('frontoffice/responsableformation/level/new_quiz.html.twig', [
            'formation'  => $formation,
            'level'      => $level,
            'quiz_types' => self::QUIZ_TYPES,
        ]);
    }

    // ──────────────────────────────────────────────
    //  DELETE A LEVEL
    // ──────────────────────────────────────────────

    #[Route('/{id}/delete', name: 'app_level_delete', methods: ['POST'])]
    public function delete(int $formationId, int $id, Request $request): Response
    {
        $formation = $this->getFormationOr404($formationId);
        $level     = $this->getLevelOr404($id, $formation);

        if (!$this->isCsrfTokenValid('delete_level' . $id, is_string($token = $request->request->get('_token')) ? $token : null)) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_level_index', ['formationId' => $formationId]);
        }

        $this->em->remove($level);
        $this->em->flush();

        $this->addFlash('success', 'Niveau supprimé.');
        return $this->redirectToRoute('app_level_index', ['formationId' => $formationId]);
    }

    // ──────────────────────────────────────────────
    //  EDIT A LEVEL
    // ──────────────────────────────────────────────

    #[Route('/{id}/edit', name: 'app_level_edit', methods: ['GET', 'POST'])]
    public function edit(int $formationId, int $id, Request $request): Response
    {
        $formation = $this->getFormationOr404($formationId);
        $level     = $this->getLevelOr404($id, $formation);

        if ($request->isMethod('POST')) {
            $titre       = trim((string) $request->request->get('titre', ''));
            $numero      = (int) $request->request->get('numero', $level->getNumero());
            $description = trim((string) $request->request->get('description', ''));
            $scoreReq    = $request->request->get('scoreRequired') !== '' ? (int) $request->request->get('scoreRequired') : null;

            if ($titre === '') {
                $this->addFlash('danger', 'Le titre est obligatoire.');
                return $this->redirectToRoute('app_level_edit', ['formationId' => $formationId, 'id' => $id]);
            }

            $level->setTitre($titre);
            $level->setNumero($numero);
            $level->setDescription($description ?: null);
            $level->setScoreRequired($scoreReq);

            $this->em->flush();

            $this->addFlash('success', 'Niveau mis à jour.');
            return $this->redirectToRoute('app_level_show', ['formationId' => $formationId, 'id' => $id]);
        }

        return $this->render('frontoffice/responsableformation/level/edit.html.twig', [
            'formation' => $formation,
            'level'     => $level,
        ]);
    }

    // ──────────────────────────────────────────────
    //  HELPERS
    // ──────────────────────────────────────────────

    private function getFormationOr404(int $id): Formation
    {
        $formation = $this->em->getRepository(Formation::class)->find($id);
        if (!$formation) {
            throw $this->createNotFoundException('Formation introuvable.');
        }
        return $formation;
    }

    private function getLevelOr404(int $id, Formation $formation): Level
    {
        $level = $this->levelRepo->find($id);
        if (!$level || $level->getFormation() !== $formation) {
            throw $this->createNotFoundException('Niveau introuvable.');
        }
        return $level;
    }
}
