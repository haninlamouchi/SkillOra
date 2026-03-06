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
use App\Service\CloudinaryService;

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
    ) {}

    // ──────────────────────────────────────────────
    //  HELPER: get the club of the logged-in responsable
    //  Throws 403 if user has no club assigned.
    // ──────────────────────────────────────────────

    private function getMyClub(): \App\Entity\Club
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        // User::$clubs is the OneToMany of clubs this user is responsible for
        $club = $user->getClubs()->first() ?: null;

        if (!$club) {
            throw $this->createAccessDeniedException('Vous n\'êtes responsable d\'aucun club.');
        }

        return $club;
    }

    /**
     * Assert the given Formation belongs to the responsable's own club.
     * Throws 403 otherwise.
     */
    private function assertOwnClub(Formation $formation): void
    {
        $myClub = $this->getMyClub();

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
        $clubId = $club->getId();
        $formations = $clubId ? $this->formationRepo->findByClub($clubId) : [];

        return $this->render('frontoffice/responsableformation/dashboard.html.twig', [
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
        $club = $this->getMyClub();
        $clubId = $club->getId();
        $formations = $clubId ? $this->formationRepo->findByClub($clubId) : [];

        return $this->render('frontoffice/responsableformation/formation/index.html.twig', [
            'club'       => $club,
            'formations' => $formations,
        ]);
    }

    #[Route('/formations/new', name: 'formation_new', methods: ['GET', 'POST'])]
    public function formationNew(Request $request,CloudinaryService $cloudinary): Response
    {
        $club = $this->getMyClub();

        $formation = new Formation();
        $formation->setClub($club);

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

            $this->em->persist($formation);
            $this->em->flush();

            $this->addFlash('success', 'Formation créée avec succès.');
            return $this->redirectToRoute('responsable_formation_index');
        }

        return $this->render('frontoffice/responsableformation/formation/new.html.twig', [
            'club' => $club,
            'form' => $form,
        ]);
    }

    #[Route('/formations/{id}', name: 'formation_show', methods: ['GET'])]
    public function formationShow(Formation $formation): Response
    {
        $this->assertOwnClub($formation);

        // Fetch participants via repository (avoids Doctrine lazy-load hydration bug)
        $formationId = $formation->getId();
        $participants = $formationId ? $this->participationRepo->findByFormation($formationId) : [];

        // Build participantScores: [userId => ['score', 'totalPoints', 'pct', 'quizTitre']]
        // Keeps only the first (most recent) result per user across all quizzes.
        $participantScores = [];

        foreach ($formation->getQuizzes() as $quiz) {
            $results = $this->resultatRepo->findBy(['quiz' => $quiz], ['datePassage' => 'DESC']);
            foreach ($results as $result) {
                $user = $result->getUser();
                if (!$user) continue;
                $uid = $user->getId();
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

        // All quiz results – pagination is handled client-side (no page reload)
        $quizResults = $this->resultatRepo->findByFormationQB($formation)->getQuery()->getResult();

        // ── Forms for modals (video + participant) ─────────────────
        $video = new Video();
        $videoForm = $this->createForm(VideoType::class, $video, [
            'action' => $this->generateUrl('app_formation_add_video', ['id' => $formation->getId()]),
        ]);

        $participation = new ParticipationFormation();
        $participationForm = $this->createForm(ParticipationFormationType::class, $participation, [
            'action' => $this->generateUrl('app_formation_add_participant', ['id' => $formation->getId()]),
        ]);

        return $this->render('frontoffice/responsableformation/formation/show.html.twig', [
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
    public function formationEdit(Request $request, Formation $formation, CloudinaryService $cloudinary): Response
    {
        $this->assertOwnClub($formation);

        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                try {
                    $url = $cloudinary->uploadImage($imageFile);
                    $formation->setImage($url);
                } catch (\RuntimeException $e) {
                    $this->addFlash('warning', 'Formation mise à jour mais l\'image n\'a pas pu être uploadée : ' . $e->getMessage());
                }
            }

            $this->em->flush();

            $this->addFlash('success', 'Formation mise à jour.');
            return $this->redirectToRoute('responsable_formation_show', ['id' => $formation->getId()]);
        }

        return $this->render('frontoffice/responsableformation/formation/edit.html.twig', [
            'formation' => $formation,
            'form'      => $form,
        ]);
    }

    #[Route('/formations/{id}/delete', name: 'formation_delete', methods: ['POST'])]
    public function formationDelete(Request $request, Formation $formation): Response
    {
        $this->assertOwnClub($formation);

        if ($this->isCsrfTokenValid('delete_formation' . $formation->getId(), is_string($token = $request->request->get('_token')) ? $token : null)) {
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

        $formationId = $formation->getId();
        $participants = $formationId ? $this->participationRepo->findByFormation($formationId) : [];

        // Build quiz results map: [userId => [ResultatQuiz, ...]]
        $quizResultsByUser = [];
        foreach ($formation->getQuizzes() as $quiz) {
            foreach ($this->resultatRepo->findBy(['quiz' => $quiz]) as $result) {
                $user = $result->getUser();
                if ($user) {
                    $quizResultsByUser[$user->getId()][] = $result;
                }
            }
        }

        return $this->render('frontoffice/responsableformation/formation/participants.html.twig', [
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

        if (!$this->isCsrfTokenValid('remove_participant' . $userId, is_string($token = $request->request->get('_token')) ? $token : null)) {
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
        $club = $this->getMyClub();

        return $this->render('frontoffice/responsableformation/club/membres.html.twig', [
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
        $club = $this->getMyClub();

        if (!$this->isCsrfTokenValid('remove_membre' . $userId, is_string($token = $request->request->get('_token')) ? $token : null)) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('responsable_club_membres');
        }

        $user = $this->em->getRepository(\App\Entity\User::class)->find($userId);
        if ($user && $club->getMembres()->contains($user)) {
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
        $club = $this->getMyClub();

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

        return $this->render('frontoffice/responsableformation/resultats.html.twig', [
            'club'               => $club,
            'resultsByFormation' => $resultsByFormation,
        ]);
    }

    // ──────────────────────────────────────────────
    //  HELPER: image upload
    // ──────────────────────────────────────────────

  
}