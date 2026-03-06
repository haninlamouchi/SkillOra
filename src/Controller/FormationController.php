<?php

namespace App\Controller;

use App\Entity\Flashcard;
use App\Entity\Formation;
use App\Entity\ParticipationFormation;
use App\Entity\Video;
use App\Form\FormationType;
use App\Form\ParticipationFormationType;
use App\Form\VideoType;
use App\Repository\FormationRepository;
use App\Repository\ParticipationFormationRepository;
use App\Repository\ResultatQuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\CloudinaryService;
use App\Service\GeminiServiceForm;

#[Route('/responsable/formation')]
final class FormationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private FormationRepository $formationRepository,
    ) {}

    // ──────────────────────────────────────────────
    //  HELPER : club du responsable connecté
    //  ✅ FIX: getClubResponsable() n'existe pas → on utilise getClubs()->first()
    // ──────────────────────────────────────────────

    private function getMyClub(): ?\App\Entity\Club
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $club = $user->getClubs()->first();
        return $club ?: null;
    }

    // ──────────────────────────────────────────────
    //  LIST
    // ──────────────────────────────────────────────

    #[Route('', name: 'app_formation_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (in_array('ROLE_RESPONSABLE_CLUB', $user->getRoles())) {
            // ✅ FIX: remplace getClubResponsable() par getClubs()->first()
            $club = $this->getMyClub();
            $formations = $club
                ? $this->formationRepository->findBy(['club' => $club], ['dateDebut' => 'DESC'])
                : [];
        } else {
            $formations = $this->formationRepository->findAll();
        }

        return $this->render('frontoffice/responsableformation/formation/index.html.twig', [
            'formations' => $formations,
        ]);
    }

    // ──────────────────────────────────────────────
    //  ADD
    // ──────────────────────────────────────────────

    #[Route('/new', name: 'app_formation_new', methods: ['GET', 'POST'])]
    public function new(Request $request,CloudinaryService $cloudinary): Response
    {
        $formation = new Formation();
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                try {
                    $url = $cloudinary->uploadImage($imageFile);
                    $formation->setImage($url);
                } catch (\RuntimeException $e) {
                    $this->addFlash('warning', 'Formation créée mais l\'image n\'a pas pu être uploadée : ' . $e->getMessage());
                }
            }


            $formation->setClub($this->getMyClub());

            $this->em->persist($formation);
            $this->em->flush();

            $this->addFlash('success', 'Formation créée avec succès.');

            return $this->redirectToRoute('app_formation_index');
        }

        return $this->render('frontoffice/responsableformation/formation/new.html.twig', [
            'formation' => $formation,
            'form' => $form,
        ]);
    }

    // ──────────────────────────────────────────────
    //  SHOW (details + videos + participants)
    // ──────────────────────────────────────────────

    #[Route('/{id}', name: 'app_formation_show', methods: ['GET', 'POST'])]
    public function show(
        Formation $formation,
        Request $request,
        ParticipationFormationRepository $participationRepo,
        ResultatQuizRepository $resultatRepo,
    ): Response {
        // --- Video form (for Bootstrap modal) ---
        $video = new Video();
        $videoForm = $this->createForm(VideoType::class, $video, [
            'action' => $this->generateUrl('app_formation_add_video', ['id' => $formation->getId()]),
        ]);

        // --- Participation form ---
        $participation = new ParticipationFormation();
        $participationForm = $this->createForm(ParticipationFormationType::class, $participation, [
            'action' => $this->generateUrl('app_formation_add_participant', ['id' => $formation->getId()]),
        ]);

        // --- Quiz results for this formation ---
        $quizResults = [];
        $participantScores = [];

        foreach ($formation->getQuizzes() as $quiz) {
            $results = $resultatRepo->findBy(['quiz' => $quiz], ['datePassage' => 'DESC']);
            foreach ($results as $result) {
                $quizResults[] = $result;

                $user = $result->getUser();
                if (!$user) continue;
                $uid = $user->getId();
                $pct = $result->getTotalPoints() > 0
                    ? round(($result->getScore() / $result->getTotalPoints()) * 100)
                    : 0;

                if (!isset($participantScores[$uid]) || $pct > $participantScores[$uid]['pct']) {
                    $quiz = $result->getQuiz();
                    $participantScores[$uid] = [
                        'score'       => $result->getScore(),
                        'totalPoints' => $result->getTotalPoints(),
                        'pct'         => $pct,
                        'quizTitre'   => $quiz ? $quiz->getTitre() : 'Quiz',
                    ];
                }
            }
        }

        return $this->render('frontoffice/responsableformation/formation/show.html.twig', [
            'formation'         => $formation,
            'videoForm'         => $videoForm,
            'participationForm' => $participationForm,
            'quizResults'       => $quizResults,
            'participantScores' => $participantScores,
        ]);
    }

    // ──────────────────────────────────────────────
    //  EDIT
    // ──────────────────────────────────────────────

    #[Route('/{id}/edit', name: 'app_formation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Formation $formation,CloudinaryService $cloudinary): Response
    {
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                try {
                    $url = $cloudinary->uploadImage($imageFile);
                    $formation->setImage($url);
                } catch (\RuntimeException $e) {
                    $this->addFlash('warning', 'Modifications enregistrées mais l\'image n\'a pas pu être uploadée : ' . $e->getMessage());
                }
            }

            // ✅ FIX: remplace getClubResponsable() par getClubs()->first()
            $formation->setClub($this->getMyClub());

            $this->em->flush();

            $this->addFlash('success', 'Formation modifiée avec succès.');

            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        return $this->render('frontoffice/responsableformation/formation/edit.html.twig', [
            'formation' => $formation,
            'form' => $form,
        ]);
    }

    // ──────────────────────────────────────────────
    //  DELETE
    // ──────────────────────────────────────────────

    #[Route('/{id}/delete', name: 'app_formation_delete', methods: ['POST'])]
    public function delete(Request $request, Formation $formation): Response
    {
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $formation->getId(), is_string($token) ? $token : null)) {
            $this->em->remove($formation);
            $this->em->flush();

            $this->addFlash('success', 'Formation supprimée avec succès.');
        }

        return $this->redirectToRoute('app_formation_index');
    }

    // ──────────────────────────────────────────────
    //  ADD VIDEO (from modal)
    // ──────────────────────────────────────────────

    #[Route('/{id}/video/add', name: 'app_formation_add_video', methods: ['POST'])]
    public function addVideo(Request $request, Formation $formation,CloudinaryService $cloudinary): Response
    {
        $video = new Video();
        $form = $this->createForm(VideoType::class, $video);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $videoFile = $form->get('videoFile')->getData();

            if (!$videoFile) {
                $this->addFlash('danger', 'Erreur lors de l\'upload du fichier vidéo.');
                return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
            }
            if($videoFile){
                try {
                    $url = $cloudinary->uploadVideo($videoFile);
                    if ($url) {
                        $video->setVideoPath($url);
                    }
                } catch (\Throwable $exception) {
                    $this->addFlash('danger', 'Échec upload vidéo: ' . $exception->getMessage());
                    return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
                }
            }

            if (!$video->getVideoPath()) {
                $this->addFlash('danger', 'Erreur lors de l\'upload du fichier vidéo.');
                return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
            }

            $video->setFormation($formation);
            $this->em->persist($video);
            $this->em->flush();

            $this->addFlash('success', 'Vidéo ajoutée avec succès.');
        } elseif ($form->isSubmitted()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                if ($error instanceof \Symfony\Component\Form\FormError) {
                    $errors[] = $error->getMessage();
                }
            }
            $this->addFlash('danger', 'Erreur : ' . (implode(', ', $errors) ?: 'Fichier vidéo invalide ou trop volumineux.'));
        }

        return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
    }

    // ──────────────────────────────────────────────
    //  DELETE VIDEO
    // ──────────────────────────────────────────────

    #[Route('/{id}/video/{videoId}/delete', name: 'app_formation_delete_video', methods: ['POST'])]
    public function deleteVideo(Request $request, Formation $formation, int $videoId): Response
    {
        $video = $this->em->getRepository(Video::class)->find($videoId);

        if ($video && $video->getFormation() === $formation) {
            $token = $request->request->get('_token');
            if ($this->isCsrfTokenValid('delete_video' . $videoId, is_string($token) ? $token : null)) {
                $videoPath = $video->getVideoPath();
                if ($videoPath) {
                    /** @var string $projectDir */
                    $projectDir = $this->getParameter('kernel.project_dir');
                    $fullPath = $projectDir . '/public' . $videoPath;
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }

                $this->em->remove($video);
                $this->em->flush();

                $this->addFlash('success', 'Vidéo supprimée.');
            }
        }

        return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
    }

    // ──────────────────────────────────────────────
    //  ADD PARTICIPANT
    // ──────────────────────────────────────────────

    #[Route('/{id}/participant/add', name: 'app_formation_add_participant', methods: ['POST'])]
    public function addParticipant(
        Request $request,
        Formation $formation,
        ParticipationFormationRepository $participationRepo,
    ): Response {
        $participation = new ParticipationFormation();
        $form = $this->createForm(ParticipationFormationType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $participation->getUser();

            if ($user && $participationRepo->isAlreadyParticipating($user, $formation)) {
                $this->addFlash('warning', 'Cet utilisateur participe déjà à cette formation.');
            } else {
                $participation->setFormation($formation);
                $this->em->persist($participation);
                $this->em->flush();

                $this->addFlash('success', 'Participant ajouté avec succès.');
            }
        }

        return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
    }

    // ──────────────────────────────────────────────
    //  REMOVE PARTICIPANT
    // ──────────────────────────────────────────────

    #[Route('/{id}/participant/{participationId}/remove', name: 'app_formation_remove_participant', methods: ['POST'])]
    public function removeParticipant(
        Request $request,
        Formation $formation,
        int $participationId,
    ): Response {
        $participation = $this->em->getRepository(ParticipationFormation::class)->find($participationId);

        if ($participation && $participation->getFormation() === $formation) {
            $token = $request->request->get('_token');
            if ($this->isCsrfTokenValid('remove_participant' . $participationId, is_string($token) ? $token : null)) {
                $this->em->remove($participation);
                $this->em->flush();

                $this->addFlash('success', 'Participant retiré.');
            }
        }

        return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
    }

    #[Route('/{id}/status', name: 'app_formation_update_status', methods: ['POST'])]
    public function updateStatus(Request $request, Formation $formation): Response
    {
        if (!$this->isCsrfTokenValid('update_status_' . $formation->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        $status = (string) $request->request->get('terminee', '0');
        if (!in_array($status, ['0', '1'], true)) {
            $this->addFlash('danger', 'Statut invalide.');
            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        $formation->setTerminee($status === '1');
        $this->em->flush();

        $this->addFlash('success', 'Statut de la formation mis à jour.');

        return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
    }

    #[Route('/{id}/flashcard/add', name: 'app_flashcard_add', methods: ['POST'])]
    public function addFlashcard(Request $request, Formation $formation, GeminiServiceForm $gemini): Response
    {
        $isAi = (string) $request->request->get('_ai') === '1';

        if ($isAi) {
            // Validate CSRF for AI path
            if (!$this->isCsrfTokenValid('ai_flashcard_' . $formation->getId(), (string) $request->request->get('_token'))) {
                $this->addFlash('danger', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
            }

            $count  = max(1, min(20, (int) $request->request->get('count', 5)));
            $prompt = trim((string) $request->request->get('prompt', ''));

            if ($prompt === '') {
                $this->addFlash('danger', 'Veuillez entrer un sujet pour la génération IA.');
                return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
            }

            $finalPrompt = "Génère exactement $count cartes mémoire en français sur le sujet suivant.\n"
                . "Utilise strictement ce format pour chaque carte (sans numérotation, sans texte supplémentaire) :\n"
                . "Question: [ta question ici]\n"
                . "Réponse: [ta réponse ici]\n\n"
                . "Sujet : $prompt";

            try {
                $aiText = $gemini->generateText($finalPrompt);
            } catch (\RuntimeException $e) {
                $this->addFlash('danger', 'Erreur IA : ' . $e->getMessage());
                return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
            }

            preg_match_all(
                '/Question\s*:\s*(.+?)\s*Réponse\s*:\s*(.+?)(?=\s*Question\s*:|\s*$)/si',
                $aiText,
                $matches
            );

            $saved = 0;
            if (!empty($matches[1])) {
                foreach ($matches[1] as $i => $q) {
                    $question = trim($q);
                    $answer   = trim($matches[2][$i] ?? '');
                    if ($question !== '' && $answer !== '') {
                        $card = (new Flashcard())
                            ->setQuestion($question)
                            ->setAnswer($answer)
                            ->setFormation($formation);
                        $this->em->persist($card);
                        $saved++;
                    }
                }
                $this->em->flush();
            }

            if ($saved > 0) {
                $this->addFlash('success', "$saved carte(s) mémoire générée(s) par l'IA avec succès !");
            } else {
                $this->addFlash('warning', "L'IA n'a pas pu générer de cartes. Reformulez votre demande.");
            }

            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        // Manual card add
        if (!$this->isCsrfTokenValid('add_flashcard_' . $formation->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        $question = trim((string) $request->request->get('question', ''));
        $answer   = trim((string) $request->request->get('answer', ''));

        if ($question === '' || $answer === '') {
            $this->addFlash('danger', 'Question et réponse sont obligatoires.');
            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        $card = (new Flashcard())
            ->setQuestion($question)
            ->setAnswer($answer)
            ->setFormation($formation);
        $this->em->persist($card);
        $this->em->flush();

        $this->addFlash('success', 'Carte mémoire ajoutée avec succès.');

        return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
    }

    #[Route('/{id}/flashcard/{cardId}/edit', name: 'app_flashcard_edit', methods: ['POST'])]
    public function editFlashcard(Request $request, Formation $formation, int $cardId): Response
    {
        $card = $this->em->getRepository(Flashcard::class)->find($cardId);

        if (!$card || $card->getFormation() !== $formation) {
            $this->addFlash('danger', 'Carte mémoire introuvable.');
            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        if (!$this->isCsrfTokenValid('edit_flashcard_' . $cardId, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        $question = trim((string) $request->request->get('question', ''));
        $answer = trim((string) $request->request->get('answer', ''));

        if ($question === '' || $answer === '') {
            $this->addFlash('danger', 'Question et réponse sont obligatoires.');
            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        $card->setQuestion($question);
        $card->setAnswer($answer);
        $this->em->flush();

        $this->addFlash('success', 'Carte mémoire modifiée avec succès.');

        return $this->redirectToRoute('app_formation_show', [
            'id' => $formation->getId(),
        ]);
    }

    #[Route('/{id}/flashcard/{cardId}/delete', name: 'app_flashcard_delete', methods: ['POST'])]
    public function deleteFlashcard(Request $request, Formation $formation, int $cardId): Response
    {
        $card = $this->em->getRepository(Flashcard::class)->find($cardId);

        if (!$card || $card->getFormation() !== $formation) {
            $this->addFlash('danger', 'Carte mémoire introuvable.');
            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        if (!$this->isCsrfTokenValid('delete_flashcard_' . $cardId, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        $this->em->remove($card);
        $this->em->flush();

        $this->addFlash('success', 'Carte mémoire supprimée.');

        return $this->redirectToRoute('app_formation_show', [
            'id' => $formation->getId(),
        ]);
    }

    // ──────────────────────────────────────────────
    //  CALENDAR
    // ──────────────────────────────────────────────

    #[Route('/calendar', name: 'app_formation_calendar', methods: ['GET'])]
    public function calendar(): Response
    {
        return $this->render('frontoffice/responsableformation/formation/calendar.html.twig');
    }

    #[Route('/calendar/events', name: 'app_formation_calendar_events', methods: ['GET'])]
    public function calendarEvents(): JsonResponse
    {
        $club = $this->getMyClub();
        $formations = $club
            ? $this->formationRepository->findBy(['club' => $club])
            : [];

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
                'url'   => $this->generateUrl('app_formation_show', ['id' => $f->getId()]),
                'color' => '#8b0000',
                'club'  => $club ? $club->getNom() : '',
            ];
        }

        return new JsonResponse($events);
    }


    // ──────────────────────────────────────────────
    //  Chat ai
    // ──────────────────────────────────────────────

    #[Route('/formation/{id}/chat-ai', name: 'formation_chat_ai', methods: ['POST'])]
    public function chatAi(
        Formation $formation,
        Request $request,
        GeminiServiceForm $gemini
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? '';

        $prompt = "
        Tu es un assistant pédagogique.

        Voici les informations de la formation :
        Titre: {$formation->getTitre()}
        Description: {$formation->getDescription()}

        Règles :
        - Détecte automatiquement la langue de la question (français, anglais ou derja tunisienne) et réponds dans la même langue.
        - Utilise un langage simple, clair et facile à comprendre.

        IMPORTANT :
        - Si la question concerne cette formation, base-toi uniquement sur les informations de la formation.
        - Si la question est générale et ne concerne pas la formation, tu peux répondre normalement avec tes connaissances générales.
        - Si tu ne connais pas la réponse, dis clairement que tu ne sais pas. N’invente jamais.

        Question de l’utilisateur :
        $userMessage
        ";

        $reply = $gemini->generateText($prompt);

        return $this->json(['reply' => $reply]);
    }
}