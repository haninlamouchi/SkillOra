<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\ParticipationFormation;
use App\Entity\User;
use App\Repository\FormationRepository;
use App\Repository\ParticipationFormationRepository;
use App\Repository\ResultatQuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    ) {}

    // ──────────────────────────────────────────────
    //  LIST — visible to everyone but filtered for students
    // ──────────────────────────────────────────────

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if ($user && $this->isGranted('ROLE_ETUDIANT')) {
            // Students only see formations from clubs they joined
            $formations = $this->formationRepo->findByStudentClubs($user);
        } else {
            $formations = $this->formationRepo->findBy([], ['dateDebut' => 'DESC']);
        }

        $participatedIds = [];
        if ($user) {
            $participatedIds = $this->participationRepo->findParticipatedFormationIds($user);
        }

        return $this->render('frontoffice/formation/index.html.twig', [
            'formations'      => $formations,
            'participatedIds' => $participatedIds,
        ]);
    }

    // ──────────────────────────────────────────────
    //  MES FORMATIONS (student only)
    // ──────────────────────────────────────────────

    #[Route('/mes-formations', name: 'mes_formations', methods: ['GET'])]
    #[IsGranted('ROLE_ETUDIANT')]
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
    #[IsGranted('ROLE_ETUDIANT')]
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

        // Students can only view formations from their clubs
        if ($user && $this->isGranted('ROLE_ETUDIANT')) {
            $studentClubIds = array_map(
                fn($c) => $c->getId(),
                $user->getClubs()->toArray()
            );

            if ($formation->getClub() && !in_array($formation->getClub()->getId(), $studentClubIds, true)) {
                throw $this->createAccessDeniedException('Cette formation n\'appartient pas à un de vos clubs.');
            }
        }

        $hasParticipated = false;
        if ($user) {
            $hasParticipated = $this->participationRepo->isAlreadyParticipating($user, $formation);
        }

        // User's own quiz results for this formation
        $quizResults = [];
        if ($user) {
            foreach ($formation->getQuizzes() as $quiz) {
                $result = $this->resultatRepo->findOneBy(['user' => $user, 'quiz' => $quiz]);
                if ($result) {
                    $quizResults[$quiz->getId()] = $result;
                }
            }
        }

        return $this->render('frontoffice/formation/show.html.twig', [
            'formation'      => $formation,
            'hasParticipated' => $hasParticipated,
            'quizResults'    => $quizResults,
        ]);
    }

    // ──────────────────────────────────────────────
    //  PARTICIPATE (students only)
    // ──────────────────────────────────────────────

    #[Route('/{id}/participer', name: 'participate', methods: ['POST'])]
    #[IsGranted('ROLE_ETUDIANT')]
    public function participate(Request $request, Formation $formation): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // CSRF check
        if (!$this->isCsrfTokenValid('participate' . $formation->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('front_formation_show', ['id' => $formation->getId()]);
        }

        // Guard: student must belong to the formation's club
        $clubIds = array_map(fn($c) => $c->getId(), $user->getClubs()->toArray());
        if ($formation->getClub() && !in_array($formation->getClub()->getId(), $clubIds, true)) {
            $this->addFlash('danger', 'Vous ne faites pas partie du club de cette formation.');
            return $this->redirectToRoute('front_formation_index');
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
    //  CANCEL PARTICIPATION (students only)
    // ──────────────────────────────────────────────

    #[Route('/{id}/annuler', name: 'cancel', methods: ['POST'])]
    #[IsGranted('ROLE_ETUDIANT')]
    public function cancel(Request $request, Formation $formation): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('cancel' . $formation->getId(), $request->request->get('_token'))) {
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
}
