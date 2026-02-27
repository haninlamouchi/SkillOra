<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\ParticipationFormation;
use App\Entity\Quiz;
use App\Entity\ResultatQuiz;
use App\Entity\Video;
use App\Form\FormationType;
use App\Form\ParticipationFormationType;
use App\Form\VideoType;
use App\Repository\ClubRepository;
use App\Repository\FormationRepository;
use App\Repository\ParticipationFormationRepository;
use App\Repository\ResultatQuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * All routes in this controller are restricted to ROLE_RESPONSABLE_CLUB.
 * Every operation is scoped to the club the currently authenticated user manages.
 */
#[Route('/responsable', name: 'responsable_')]
#[IsGranted('ROLE_RESPONSABLE_CLUB')]
class ResponsableFormationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private FormationRepository $formationRepo,
        private ParticipationFormationRepository $participationRepo,
        private ResultatQuizRepository $resultatRepo,
        private ClubRepository $clubRepo,
    ) {}

    // ──────────────────────────────────────────────
    //  HELPER: get the club of the logged-in responsable
    //  Throws 403 if user has no club assigned.
    // ──────────────────────────────────────────────

    private function getMyClub(): ?\App\Entity\Club
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        return $user->getClubs()->first() ?: null;
    }

    private function requireMyClub(): \App\Entity\Club
    {
        $club = $this->getMyClub();
        if (!$club) {
            throw $this->createNotFoundException('Aucun club ne vous est assigné.');
        }
        return $club;
    }

    /**
     * Assert the given Formation belongs to the responsable's own club.
     * Throws 403 otherwise.
     */
    private function assertOwnClub(Formation $formation): void
    {
        $myClub = $this->requireMyClub();

        if ($formation->getClub()?->getId() !== $myClub->getId()) {
            throw $this->createAccessDeniedException('Cette formation n\'appartient pas à votre club.');
        }
    }

    // ──────────────────────────────────────────────
    //  DASHBOARD
    // ──────────────────────────────────────────────

    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $club = $this->getMyClub();

        if (!$club) {
            return $this->render('frontoffice/responsable/no_club.html.twig');
        }

        $formations = $this->formationRepo->findByClub($club->getId());

        return $this->render('frontoffice/responsable/dashboard.html.twig', [
            'club'       => $club,
            'formations' => $formations,
        ]);
    }

    // ──────────────────────────────────────────────
    //  CRUD FORMATIONS
    // ──────────────────────────────────────────────

    #[Route('/formations', name: 'formation_index', methods: ['GET'])]
    public function formationIndex(): Response
    {
        $club = $this->requireMyClub();
        $formations = $this->formationRepo->findByClub($club->getId());

        return $this->render('frontoffice/responsable/formation/index.html.twig', [
            'club'       => $club,
            'formations' => $formations,
        ]);
    }

    #[Route('/formations/new', name: 'formation_new', methods: ['GET', 'POST'])]
    public function formationNew(Request $request, SluggerInterface $slugger): Response
    {
        $club = $this->requireMyClub();

        $formation = new Formation();
        $formation->setClub($club);

        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $formation, $slugger);

            $this->em->persist($formation);
            $this->em->flush();

            $this->addFlash('success', 'Formation créée avec succès.');
            return $this->redirectToRoute('responsable_formation_index');
        }

        return $this->render('frontoffice/responsable/formation/new.html.twig', [
            'club' => $club,
            'form' => $form,
        ]);
    }

    #[Route('/formations/{id}', name: 'formation_show', methods: ['GET'])]
    public function formationShow(Formation $formation): Response
    {
        $this->assertOwnClub($formation);

        // Fetch participants via repository (avoids Doctrine lazy-load hydration bug)
        $participants = $this->participationRepo->findByFormation($formation->getId());

        // Build participantScores: [userId => ['score', 'totalPoints', 'pct', 'quizTitre']]
        // Keeps only the first (most recent) result per user across all quizzes.
        $participantScores = [];

        // Build quizResults: flat ordered list of ALL ResultatQuiz for this formation
        $quizResults = [];

        foreach ($formation->getQuizzes() as $quiz) {
            $results = $this->resultatRepo->findBy(['quiz' => $quiz], ['datePassage' => 'DESC']);
            foreach ($results as $result) {
                $quizResults[] = $result;

                $uid = $result->getUser()->getId();
                if (!isset($participantScores[$uid])) {
                    $total = $result->getTotalPoints();
                    $pct   = $total > 0
                        ? round(($result->getScore() / $total) * 100, 1)
                        : 0;

                    $participantScores[$uid] = [
                        'score'      => $result->getScore(),
                        'totalPoints'=> $total,
                        'pct'        => $pct,
                        'quizTitre'  => $quiz->getTitre(),
                    ];
                }
            }
        }

        // ── Forms for modals (video + participant) ─────────────────
        $video = new Video();
        $videoForm = $this->createForm(VideoType::class, $video, [
            'action' => $this->generateUrl('app_formation_add_video', ['id' => $formation->getId()]),
        ]);

        $participation = new ParticipationFormation();
        $participationForm = $this->createForm(ParticipationFormationType::class, $participation, [
            'action' => $this->generateUrl('app_formation_add_participant', ['id' => $formation->getId()]),
        ]);

        return $this->render('frontoffice/responsable/formation/show.html.twig', [
            'formation'         => $formation,
            'participants'      => $participants,
            'participantScores' => $participantScores,
            'quizResults'       => $quizResults,
            // ✅ FIX: le template utilise 'videoType' — on expose les deux noms pour compatibilité
            'videoType'         => $videoForm,
            'videoForm'         => $videoForm,
            'participationForm' => $participationForm,
        ]);
    }

    #[Route('/formations/{id}/edit', name: 'formation_edit', methods: ['GET', 'POST'])]
    public function formationEdit(Request $request, Formation $formation, SluggerInterface $slugger): Response
    {
        $this->assertOwnClub($formation);

        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $formation, $slugger);
            $this->em->flush();

            $this->addFlash('success', 'Formation mise à jour.');
            return $this->redirectToRoute('responsable_formation_show', ['id' => $formation->getId()]);
        }

        return $this->render('frontoffice/responsable/formation/edit.html.twig', [
            'formation' => $formation,
            'form'      => $form,
        ]);
    }

    #[Route('/formations/{id}/delete', name: 'formation_delete', methods: ['POST'])]
    public function formationDelete(Request $request, Formation $formation): Response
    {
        $this->assertOwnClub($formation);

        if ($this->isCsrfTokenValid('delete_formation' . $formation->getId(), $request->request->get('_token'))) {
            $this->em->remove($formation);
            $this->em->flush();
            $this->addFlash('success', 'Formation supprimée.');
        }

        return $this->redirectToRoute('responsable_formation_index');
    }

    // ──────────────────────────────────────────────
    //  PARTICIPANTS MANAGEMENT
    // ──────────────────────────────────────────────

    /**
     * List participants of a formation with their quiz scores.
     */
    #[Route('/formations/{id}/participants', name: 'formation_participants', methods: ['GET'])]
    public function formationParticipants(Formation $formation): Response
    {
        $this->assertOwnClub($formation);

        $participants = $this->participationRepo->findByFormation($formation->getId());

        // Build quiz results map: [userId => [ResultatQuiz, ...]]
        $quizResultsByUser = [];
        foreach ($formation->getQuizzes() as $quiz) {
            foreach ($this->resultatRepo->findBy(['quiz' => $quiz]) as $result) {
                $quizResultsByUser[$result->getUser()->getId()][] = $result;
            }
        }

        return $this->render('frontoffice/responsable/formation/participants.html.twig', [
            'formation'         => $formation,
            'participants'      => $participants,
            'quizResultsByUser' => $quizResultsByUser,
        ]);
    }

    /**
     * Remove a participant from a formation.
     */
    #[Route('/formations/{id}/participants/{userId}/remove', name: 'formation_participant_remove', methods: ['POST'])]
    public function removeParticipant(
        Request $request,
        Formation $formation,
        int $userId,
    ): Response {
        $this->assertOwnClub($formation);

        if (!$this->isCsrfTokenValid('remove_participant' . $userId, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('responsable_formation_participants', ['id' => $formation->getId()]);
        }

        $participation = $this->participationRepo->findOneBy([
            'formation' => $formation,
            'user'      => $userId,
        ]);

        if ($participation) {
            $this->em->remove($participation);
            $this->em->flush();
            $this->addFlash('success', 'Participant retiré de la formation.');
        }

        return $this->redirectToRoute('responsable_formation_participants', ['id' => $formation->getId()]);
    }

    // ──────────────────────────────────────────────
    //  CLUB MEMBERS MANAGEMENT
    // ──────────────────────────────────────────────

    /**
     * List all members of the responsable's club.
     */
    #[Route('/club/membres', name: 'club_membres', methods: ['GET'])]
    public function clubMembres(): Response
    {
        $club = $this->requireMyClub();

        return $this->render('frontoffice/responsable/club/membres.html.twig', [
            'club'    => $club,
            'membres' => $club->getMembres(),
        ]);
    }

    /**
     * Remove a user from the responsable's club.
     */
    #[Route('/club/membres/{userId}/remove', name: 'club_membre_remove', methods: ['POST'])]
    public function removeClubMembre(Request $request, int $userId): Response
    {
        $club = $this->requireMyClub();

        if (!$this->isCsrfTokenValid('remove_membre' . $userId, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('responsable_club_membres');
        }

        $user = $this->em->getRepository(\App\Entity\User::class)->find($userId);
        if ($user && $club->hasMembre($user)) {
            $club->removeMembre($user);
            $this->em->flush();
            $this->addFlash('success', $user->getFullName() . ' a été retiré du club.');
        }

        return $this->redirectToRoute('responsable_club_membres');
    }

    // ──────────────────────────────────────────────
    //  QUIZ RESULTS
    // ──────────────────────────────────────────────

    /**
     * Display all quiz results across all formations of the responsable's club.
     */
    #[Route('/resultats', name: 'resultats', methods: ['GET'])]
    public function resultats(): Response
    {
        $club = $this->requireMyClub();

        $resultsByFormation = [];
        foreach ($club->getFormations() as $formation) {
            $formationResults = [];
            foreach ($formation->getQuizzes() as $quiz) {
                $results = $this->resultatRepo->findBy(
                    ['quiz' => $quiz],
                    ['datePassage' => 'DESC']
                );
                foreach ($results as $result) {
                    $formationResults[] = $result;
                }
            }
            if (!empty($formationResults)) {
                $resultsByFormation[$formation->getId()] = [
                    'formation' => $formation,
                    'results'   => $formationResults,
                ];
            }
        }

        return $this->render('frontoffice/responsable/resultats.html.twig', [
            'club'               => $club,
            'resultsByFormation' => $resultsByFormation,
        ]);
    }

    // ──────────────────────────────────────────────
    //  HELPER: image upload
    // ──────────────────────────────────────────────

    private function handleImageUpload(
        \Symfony\Component\Form\FormInterface $form,
        Formation $formation,
        SluggerInterface $slugger,
    ): void {
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile */
        $imageFile = $form->get('imageFile')->getData();
        if (!$imageFile) {
            return;
        }

        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename     = $slugger->slug($originalFilename);
        $newFilename      = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

        try {
            $imageFile->move(
                $this->getParameter('formations_images_directory'),
                $newFilename
            );
        } catch (FileException) {
            $this->addFlash('warning', 'Impossible d\'uploader l\'image.');
            return;
        }

        $formation->setImage($newFilename);
    }
}
