<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\FormationRepository;
use App\Repository\ParticipationFormationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FrontofficeController extends AbstractController
{
    public function __construct(
        private FormationRepository $formationRepo,
        private ParticipationFormationRepository $participationRepo,
    ) {}

    #[Route('/', name: 'front_home')]
    public function index(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        $mesFormations   = [];
        $allFormations   = [];
        $participatedIds = [];
        $myClubs         = [];

        if ($user && $this->isGranted('ROLE_ETUDIANT')) {
            // "Mes Formations": formations the student actively participates in
            $mesFormations = $this->formationRepo->findByParticipant($user);

            // Catalogue: only formations from clubs the student joined
            $allFormations = $this->formationRepo->findByStudentClubs($user);

            $participatedIds = $this->participationRepo->findParticipatedFormationIds($user);
            $myClubs = $user->getClubs()->toArray();
        } elseif ($user && $this->isGranted('ROLE_RESPONSABLE_CLUB')) {
            // Responsable lands on their own dashboard
            return $this->redirectToRoute('responsable_dashboard');
        } else {
            // Guests / admins see everything
            $allFormations = $this->formationRepo->findBy([], ['dateDebut' => 'DESC']);
        }

        return $this->render('frontoffice/home.html.twig', [
            'mesFormations'   => $mesFormations,
            'allFormations'   => $allFormations,
            'participatedIds' => $participatedIds,
            'myClubs'         => $myClubs,
        ]);
    }
}
