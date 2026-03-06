<?php

namespace App\Controller;

use App\Entity\Favorite;
use App\Entity\Formation;
use App\Entity\ParticipationFormation;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\FormationRepository;
use App\Repository\ParticipationFormationRepository;
use App\Repository\ResultatQuizRepository;
use App\Service\GeminiServiceForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/formations', name: 'front_formation_')]
class FrontFormationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private FormationRepository $formationRepo,
        private ParticipationFormationRepository $participationRepo,
        private ResultatQuizRepository $resultatRepo,
        private FavoriteRepository $favoriteRepo,
    ) {}

    // ──────────────────────────────────────────────
    //  LIST — visible to everyone but filtered for students
    // ──────────────────────────────────────────────

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if ($user && $this->isGranted('ROLE_MEMBRE')) {
            // Les membres ne voient que les formations des clubs auxquels ils appartiennent
            $formations = $this->formationRepo->findByStudentClubs($user);
        } else {
            $formations = $this->formationRepo->findBy([], ['dateDebut' => 'DESC']);
        }

        $participatedIds = [];
        if ($user) {
            $participatedIds = $this->participationRepo->findParticipatedFormationIds($user);
        }


        $favoritesIds = [];
        if ($user) {
            $favoritesIds = $this->favoriteRepo->findUserFavoritesIds($user);
        }

        // Favorites-only filter
        $onlyFavorites = false;

        return $this->render('frontoffice/formation/index.html.twig', [
            'formations'      => $formations,
            'participatedIds' => $participatedIds,
            'favoritesIds'    => $favoritesIds,
        ]);
    }

    // ──────────────────────────────────────────────
    //  MES FORMATIONS (student only)
    // ──────────────────────────────────────────────

    #[Route('/mes-formations', name: 'mes_formations', methods: ['GET'])]
    #[IsGranted('ROLE_MEMBRE')]
    public function mesFormations(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $mesFormations = $this->formationRepo->findByParticipant($user);
        $participatedIds = $this->participationRepo->findParticipatedFormationIds($user);

        return $this->render('frontoffice/formation/mes_formations.html.twig', [
            'formations'      => $mesFormations,
            'participatedIds' => $participatedIds,
        ]);
    }

    // ──────────────────────────────────────────────
    //  EN COURS — active formations the student participates in
    // ──────────────────────────────────────────────

    #[Route('/en-cours', name: 'en_cours', methods: ['GET'])]
    #[IsGranted('ROLE_MEMBRE')]
    public function enCours(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $formations = $this->formationRepo->findActiveByParticipant($user);
        $participatedIds = $this->participationRepo->findParticipatedFormationIds($user);

        return $this->render('frontoffice/formation/en_cours.html.twig', [
            'formations'      => $formations,
            'participatedIds' => $participatedIds,
        ]);
    }

    // ──────────────────────────────────────────────
    //  SHOW FORMATION DETAILS
    // ──────────────────────────────────────────────

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Formation $formation): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        // Les membres ne peuvent consulter que les formations de leurs clubs (relation membres)
        if ($user instanceof \App\Entity\User && $this->isGranted('ROLE_MEMBRE')) {
            $memberClubIds = array_map(
                fn($c) => $c->getId(),
                $user->getMemberClubs()->toArray()
            );

            if ($formation->getClub() && !in_array($formation->getClub()->getId(), $memberClubIds, true)) {
                throw $this->createAccessDeniedException('Cette formation n\'appartient pas \u00e0 un de vos clubs.');
            }
        }

        $hasParticipated = false;
        if ($user instanceof \App\Entity\User) {
            if ($formation->isIsPaid()) {
                // For paid formations, only mark as participated when the user has actually paid
                $hasParticipated = $this->participationRepo->hasPaidParticipation($user, $formation);
            } else {
                $hasParticipated = $this->participationRepo->isAlreadyParticipating($user, $formation);
            }
        }

        // ── Level progression: sort levels by numero, compute unlock + best score ──
        $sortedLevels = $formation->getLevels()->toArray();
        usort($sortedLevels, fn($a, $b) => $a->getNumero() <=> $b->getNumero());

        // Per-quiz result map: [quizId => ['done', 'pct', 'passed']]
        $quizProgress = [];
        if ($user instanceof \App\Entity\User) {
            foreach ($sortedLevels as $level) {
                foreach ($level->getQuizzes() as $quiz) {
                    $result = $this->resultatRepo->findOneBy(
                        ['user' => $user, 'quiz' => $quiz],
                        ['score' => 'DESC']
                    );
                    if ($result) {
                        $pct = $result->getTotalPoints() > 0
                            ? (int) round(($result->getScore() / $result->getTotalPoints()) * 100)
                            : 0;
                        $quizProgress[$quiz->getId()] = [
                            'done'      => true,
                            'pct'       => $pct,
                            'passed'    => $pct >= 50,
                            'score'     => $result->getScore(),
                            'maxPoints' => $result->getTotalPoints(),
                        ];
                    } else {
                        $quizProgress[$quiz->getId()] = ['done' => false, 'pct' => null, 'passed' => false, 'score' => 0, 'maxPoints' => 0];
                    }
                }
            }
        }

        // Level passed = ALL its quizzes are passed (>= 50%). Score = aggregate across all quizzes.
        $levelProgress = [];
        $previousPassed = true;
        foreach ($sortedLevels as $level) {
            $quizzes      = $level->getQuizzes();
            $levelPassed  = !$quizzes->isEmpty();
            $totalScore   = 0;
            $totalMax     = 0;
            $anyDone      = false;
            foreach ($quizzes as $quiz) {
                $qp = $quizProgress[$quiz->getId()] ?? ['done' => false, 'pct' => null, 'passed' => false, 'score' => 0, 'maxPoints' => 0];
                if (!$qp['passed']) $levelPassed = false;
                if ($qp['done']) {
                    $anyDone    = true;
                    $totalScore += $qp['score'];
                    $totalMax   += $qp['maxPoints'];
                }
            }
            $bestPct = ($anyDone && $totalMax > 0) ? (int) round($totalScore / $totalMax * 100) : null;
            $levelProgress[$level->getId()] = [
                'unlocked' => $previousPassed,
                'passed'   => $levelPassed,
                'bestPct'  => $bestPct,
                'required' => 50,
            ];
            $previousPassed = $levelPassed;
        }

        $favoritesIds = [];
        if ($user) {
            $favoritesIds = $this->favoriteRepo->findUserFavoritesIds($user);
        }

        return $this->render('frontoffice/formation/show.html.twig', [
            'formation'       => $formation,
            'hasParticipated' => $hasParticipated,
            'sortedLevels'    => $sortedLevels,
            'levelProgress'   => $levelProgress,
            'quizProgress'    => $quizProgress,
            'favoritesIds'    => $favoritesIds,
        ]);
    }

    // ──────────────────────────────────────────────
    //  PARTICIPATE (students only)
    // ──────────────────────────────────────────────

    #[Route('/{id}/participer', name: 'participate', methods: ['POST'])]
    #[IsGranted('ROLE_MEMBRE')]
    public function participate(Request $request, Formation $formation): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // CSRF check
        if (!$this->isCsrfTokenValid('participate' . $formation->getId(), is_string($token = $request->request->get('_token')) ? $token : null)) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('front_formation_show', ['id' => $formation->getId()]);
        }

        // Guard: le membre doit appartenir au club de la formation
        $clubIds = array_map(fn($c) => $c->getId(), $user->getMemberClubs()->toArray());
        if ($formation->getClub() && !in_array($formation->getClub()->getId(), $clubIds, true)) {
            $this->addFlash('danger', 'Vous ne faites pas partie du club de cette formation.');
            return $this->redirectToRoute('front_formation_index');
        }

        // Guard: paid formations must go through Stripe
        if ($formation->isIsPaid()) {
            $this->addFlash('warning', 'Cette formation est payante. Veuillez passer par le paiement Stripe.');
            return $this->redirectToRoute('front_formation_show', ['id' => $formation->getId()]);
        }

        // Guard: no duplicate
        if ($this->participationRepo->isAlreadyParticipating($user, $formation)) {
            $this->addFlash('warning', 'Vous participez déjà à cette formation.');
            return $this->redirectToRoute('front_formation_show', ['id' => $formation->getId()]);
        }

        $participation = new ParticipationFormation();
        $participation->setUser($user);
        $participation->setFormation($formation);

        $this->em->persist($participation);
        $this->em->flush();

        $this->addFlash('success', 'Vous êtes maintenant inscrit à « ' . $formation->getTitre() . ' » !');

        return $this->redirectToRoute('front_formation_show', ['id' => $formation->getId()]);
    }

    // ──────────────────────────────────────────────
    //  TOGGLE FAVORITE
    // ──────────────────────────────────────────────

    #[Route('/{id}/favori', name: 'toggle_favorite', methods: ['POST'])]
    #[IsGranted('ROLE_MEMBRE')]
    public function toggleFavorite(Request $request, Formation $formation): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('fav' . $formation->getId(), is_string($token = $request->request->get('_token')) ? $token : null)) {
            $this->addFlash('danger', 'Token invalide.');
            return $this->redirectToRoute('front_formation_show', ['id' => $formation->getId()]);
        }

        $existing = $this->favoriteRepo->findByUserAndFormation($user, $formation);

        if ($existing) {
            $this->em->remove($existing);
            $this->em->flush();
            $this->addFlash('info', '« ' . $formation->getTitre() . ' » retiré des favoris.');
        } else {
            $fav = new Favorite();
            $fav->setUser($user);
            $fav->setFormation($formation);
            $this->em->persist($fav);
            $this->em->flush();
            $this->addFlash('success', '« ' . $formation->getTitre() . ' » ajouté aux favoris !');
        }

        $referer = $request->headers->get('referer');
        return $referer
            ? $this->redirect($referer)
            : $this->redirectToRoute('front_formation_index');
    }

    // ──────────────────────────────────────────────
    //  CANCEL PARTICIPATION (students only)
    // ──────────────────────────────────────────────

    #[Route('/{id}/annuler', name: 'cancel', methods: ['POST'])]
    #[IsGranted('ROLE_MEMBRE')]
    public function cancel(Request $request, Formation $formation): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('cancel' . $formation->getId(), is_string($token = $request->request->get('_token')) ? $token : null)) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('front_formation_show', ['id' => $formation->getId()]);
        }

        $participation = $this->participationRepo->findOneBy([
            'user'      => $user,
            'formation' => $formation,
        ]);

        if ($participation) {
            $this->em->remove($participation);
            $this->em->flush();
            $this->addFlash('success', 'Votre participation a été annulée.');
        }

        return $this->redirectToRoute('front_formation_show', ['id' => $formation->getId()]);
    }

    // ──────────────────────────────────────────────
    //  CALENDAR
    // ──────────────────────────────────────────────

    #[Route('/calendar', name: 'calendar', methods: ['GET'])]
    public function calendar(): Response
    {
        return $this->render('frontoffice/formation/calendar.html.twig');
    }

    #[Route('/calendar/events', name: 'calendar_events', methods: ['GET'])]
    public function calendarEvents(): JsonResponse
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if ($user && $this->isGranted('ROLE_MEMBRE')) {
            $formations = $this->formationRepo->findByStudentClubs($user);
        } else {
            $formations = $this->formationRepo->findAll();
        }

        $events = [];
        foreach ($formations as $f) {
            if (!$f instanceof \App\Entity\Formation || !$f->getDateDebut()) {
                continue;
            }
            $title = $f->getTitre() ?? 'Formation';
            $events[] = [
                'id'    => $f->getId(),
                'title' => $title,
                'start' => $f->getDateDebut()->format('Y-m-d'),
                'end'   => $f->getDateFin() ? $f->getDateFin()->modify('+1 day')->format('Y-m-d') : null,
                'url'   => $this->generateUrl('front_formation_show', ['id' => $f->getId()]),
                'color' => '#8b0000',
                'club'  => $f->getClub() ? $f->getClub()->getNom() : '',
            ];
        }

        return new JsonResponse($events);
    }

    // ──────────────────────────────────────────────
    //  CHATBOT AI
    // ──────────────────────────────────────────────

    #[Route('/{id}/chat', name: 'chatbot', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function chatbot(Formation $formation, Request $request, GeminiServiceForm $gemini): JsonResponse
    {
        $data        = json_decode($request->getContent(), true);
        $userMessage = trim((string) ($data['message'] ?? ''));

        if ($userMessage === '') {
            return $this->json(['error' => 'Message vide.'], 400);
        }

        $context = sprintf(
            "Tu es un assistant pédagogique intelligent pour la formation \"%s\".\n" .
            "Description : %s\n" .
            "Réponds en français, de façon claire et concise.\n" .
            "Si la question est hors sujet, ramène poliment à la formation.\n" .
            "Question de l'apprenant : %s",
            $formation->getTitre(),
            $formation->getDescription() ?? 'N/A',
            $userMessage
        );

        try {
            $reply = $gemini->generateText($context);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 503);
        }

        return $this->json(['reply' => $reply]);
    }
}
