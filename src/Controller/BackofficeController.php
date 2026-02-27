<?php

namespace App\Controller;

use App\Entity\Formation;
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
class BackofficeController extends AbstractController
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
        $responsables = $this->userRepo->findByRole('responsable_club');

        return $this->render('backoffice/dashboard.html.twig', [
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

                $uid = $result->getUser()->getId();
                $pct = $result->getTotalPoints() > 0
                    ? round(($result->getScore() / $result->getTotalPoints()) * 100)
                    : 0;

                if (!isset($participantScores[$uid]) || $pct > $participantScores[$uid]['pct']) {
                    $participantScores[$uid] = [
                        'score'       => $result->getScore(),
                        'totalPoints' => $result->getTotalPoints(),
                        'pct'         => $pct,
                        'quizTitre'   => $result->getQuiz()->getTitre(),
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
    // FORMATION DELETE
    // ---------------------------------------------

    #[Route('/formation/{id}/delete', name: 'formation_delete', methods: ['POST'])]
    public function formationDelete(Request $request, Formation $formation): Response
    {
        if ($this->isCsrfTokenValid('delete' . $formation->getId(), $request->request->get('_token'))) {
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
