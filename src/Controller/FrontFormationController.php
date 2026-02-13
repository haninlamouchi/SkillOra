<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\ParticipationFormation;
use App\Repository\FormationRepository;
use App\Repository\ParticipationFormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/formations', name: 'front_formation_')]
class FrontFormationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private FormationRepository $formationRepo,
        private ParticipationFormationRepository $participationRepo,
    ) {}

    // ──────────────────────────────────────────────
    //  LIST ALL FORMATIONS
    // ──────────────────────────────────────────────

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $formations = $this->formationRepo->findBy([], ['dateDebut' => 'DESC']);

        // Check participation status for logged-in user
        $participatedIds = [];
        if ($this->getUser()) {
            $participations = $this->participationRepo->findBy(['user' => $this->getUser()]);
            foreach ($participations as $p) {
                $participatedIds[] = $p->getFormation()->getId();
            }
        }

        return $this->render('frontoffice/formation/index.html.twig', [
            'formations' => $formations,
            'participatedIds' => $participatedIds,
        ]);
    }

    // ──────────────────────────────────────────────
    //  SHOW FORMATION DETAILS
    // ──────────────────────────────────────────────

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Formation $formation): Response
    {
        $hasParticipated = false;
        if ($this->getUser()) {
            $existing = $this->participationRepo->findOneBy([
                'user' => $this->getUser(),
                'formation' => $formation,
            ]);
            $hasParticipated = $existing !== null;
        }

        return $this->render('frontoffice/formation/show.html.twig', [
            'formation' => $formation,
            'hasParticipated' => $hasParticipated,
        ]);
    }

    // ──────────────────────────────────────────────
    //  PARTICIPATE IN FORMATION
    // ──────────────────────────────────────────────

    #[Route('/{id}/participer', name: 'participate', methods: ['POST'])]
    public function participate(Request $request, Formation $formation): Response
    {
        // Check CSRF
        if (!$this->isCsrfTokenValid('participate' . $formation->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('front_formation_show', ['id' => $formation->getId()]);
        }

        // Check if already participated
        $existing = $this->participationRepo->findOneBy([
            'user' => $this->getUser(),
            'formation' => $formation,
        ]);

        if ($existing) {
            $this->addFlash('warning', 'Vous participez déjà à cette formation.');
            return $this->redirectToRoute('front_formation_show', ['id' => $formation->getId()]);
        }

        $participation = new ParticipationFormation();
        $participation->setUser($this->getUser());
        $participation->setFormation($formation);

        $this->em->persist($participation);
        $this->em->flush();

        $this->addFlash('success', 'Vous êtes maintenant inscrit à la formation « ' . $formation->getTitre() . ' » !');

        return $this->redirectToRoute('front_formation_show', ['id' => $formation->getId()]);
    }
}
