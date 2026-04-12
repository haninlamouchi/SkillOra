<?php

namespace App\Controller\user;

use App\Entity\Club;
use App\Entity\DemandeAdhesion;
use App\Entity\DemandeMembre;
use App\Entity\DemandeClub;
use App\Form\DemandeClubType;
use App\Form\DemandeAdhesionType;
use App\Repository\ClubRepository;
use App\Repository\DemandeMembreRepository;
use App\Repository\DemandeAdhesionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Enum\StatutDemande;
use App\Event\DemandeMembreEvent;
use App\Event\DemandeClubEvent;
use App\Event\DemandeResponsableEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\GeminiService;

#[Route('/user/club')]
class UserClubController extends AbstractController
{
    // ──────────────────────────────────────────────
    // ROUTES STATIQUES EN PREMIER
    // ──────────────────────────────────────────────

 #[Route('/', name: 'user_club_index', methods: ['GET'])]
public function index(
    ClubRepository $clubRepository,
    PaginatorInterface $paginator,
    Request $request
): Response {
    $user       = $this->getUser();
    $searchNom  = (string) $request->query->get('nom', '');

    $qb = $clubRepository->createQueryBuilder('c')
        ->where('c.responsable != :user OR c.responsable IS NULL')
        ->setParameter('user', $user)
        ->orderBy('c.nom', 'ASC');

    if ($searchNom !== '') {
        $qb->andWhere('c.nom LIKE :nom')
           ->setParameter('nom', '%' . $searchNom . '%');
    }

    $clubs = $paginator->paginate(
        $qb->getQuery(),
        $request->query->getInt('page', 1),
        6
    );

    return $this->render('frontoffice/user/indexMbreC.html.twig', [
        'clubs'     => $clubs,
        'searchNom' => $searchNom,
    ]);
}

    #[Route('/mes-clubs', name: 'user_mes_clubs', methods: ['GET'])]
    public function mesClubs(
        DemandeMembreRepository $demandeMembreRepository
    ): Response {
        $user = $this->getUser();

        $demandes = $demandeMembreRepository->findBy([
            'user'   => $user,
            'statut' => StatutDemande::accepte,
        ]);

        $clubs = array_map(fn($d) => $d->getClub(), $demandes);

        return $this->render('frontoffice/user/mesClubs.html.twig', [
            'clubs' => $clubs,
        ]);
    }

    #[Route('/recommandations', name: 'user_club_recommandations', methods: ['GET', 'POST'])]
    public function recommandations(
        Request $request,
        ClubRepository $clubRepository,
        GeminiService $geminiService
    ): Response {
        $clubs = $clubRepository->findAll();
        $recommandes = [];

        if ($request->isMethod('POST')) {
            $interets = (string) $request->request->get('interets', '');
            if ($interets) {
                try {
                    $recommandes = $geminiService->recommanderClubs($interets, $clubs);
                } catch (\Exception $e) {
                    dd($e->getMessage());
                }
            }
        }

        return $this->render('frontoffice/user/recommandations.html.twig', [
            'clubs'       => $clubs,
            'recommandes' => $recommandes,
        ]);
    }

   #[Route('/new', name: 'user_club_new', methods: ['GET', 'POST'])]
public function new(
    Request $request,
    EntityManagerInterface $entityManager,
    EventDispatcherInterface $dispatcher
): Response {
    $demande = new DemandeClub();
    $form = $this->createForm(DemandeClubType::class, $demande);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // ── Gestion upload CV ──
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $cvFile */
        $cvFile = $form->get('cvFile')->getData();

        if ($cvFile) {
            $newFilename = uniqid('cv_') . '.' . $cvFile->guessExtension();
            try {
                $cvFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/cvs',
                    $newFilename
                );
                $demande->setCv($newFilename);
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de l\'upload du CV. Veuillez réessayer.');
                return $this->render('frontoffice/user/newUser.html.twig', [
                    'form' => $form,
                ]);
            }
        }
        // ── fin gestion CV ──

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $demande->setResponsable($user);
        $entityManager->persist($demande);
        $entityManager->flush();
        $dispatcher->dispatch(new DemandeClubEvent($demande), DemandeClubEvent::NAME);
        $this->addFlash('success', 'Votre demande a été envoyée, en attente de validation.');
        return $this->redirectToRoute('user_club_index');
    }

    return $this->render('frontoffice/user/newUser.html.twig', [
        'form' => $form,
    ]);
}

    // ──────────────────────────────────────────────
    // ROUTES DYNAMIQUES EN DERNIER
    // ──────────────────────────────────────────────

    #[Route('/{id}/adhesion', name: 'user_club_adhesion', methods: ['POST'])]
    public function adhesion(
        Club $club,
        EntityManagerInterface $entityManager,
        DemandeMembreRepository $repo,
        EventDispatcherInterface $dispatcher
    ): Response {
        $user = $this->getUser();

        $dejaMembre = $repo->findOneBy([
            'club'   => $club,
            'user'   => $user,
            'statut' => StatutDemande::accepte,
        ]);

        if ($dejaMembre) {
            $this->addFlash('warning', 'Vous êtes déjà membre de ce club.');
            return $this->redirectToRoute('user_club_index');
        }

        $dejaEnAttente = $repo->findOneBy([
            'club'   => $club,
            'user'   => $user,
            'statut' => StatutDemande::en_attente,
        ]);

        if ($dejaEnAttente) {
            $this->addFlash('warning', 'Vous avez déjà une demande d\'adhésion en attente.');
            return $this->redirectToRoute('user_club_index');
        }

        $demande = new DemandeMembre();
        $demande->setClub($club);
        /** @var \App\Entity\User|null $user */
        $demande->setUser($user);
        $demande->setDateInscription(new \DateTime());
        $demande->setStatut(StatutDemande::en_attente);

        $entityManager->persist($demande);
        $entityManager->flush();

        $dispatcher->dispatch(new DemandeMembreEvent($demande), DemandeMembreEvent::NAME);

        $this->addFlash('success', 'Votre demande d\'adhésion a été envoyée au responsable.');
        return $this->redirectToRoute('user_club_index');
    }

    #[Route('/{id}/responsable', name: 'user_club_responsable', methods: ['GET', 'POST'])]
    public function responsable(
        Club $club,
        Request $request,
        EntityManagerInterface $entityManager,
        DemandeMembreRepository $membreRepo,
        DemandeAdhesionRepository $adhesionRepo,
        EventDispatcherInterface $dispatcher
    ): Response {
        $user = $this->getUser();

        // Vérifier que l'user est bien membre du club
        $dejaMembre = $membreRepo->findOneBy([
            'club'   => $club,
            'user'   => $user,
            'statut' => StatutDemande::accepte,
        ]);

        if (!$dejaMembre) {
            $this->addFlash('danger', 'Vous devez être membre du club pour demander à en devenir responsable.');
            return $this->redirectToRoute('user_club_index');
        }

        // Vérifier qu'il n'a pas déjà une demande en attente
        $dejaEnAttente = $adhesionRepo->findOneBy([
            'club'   => $club,
            'user'   => $user,
            'statut' => StatutDemande::en_attente,
        ]);

        if ($dejaEnAttente) {
            $this->addFlash('warning', 'Vous avez déjà une demande de responsable en attente.');
            return $this->redirectToRoute('user_mes_clubs');
        }

        $demande = new DemandeAdhesion();
        $form = $this->createForm(DemandeAdhesionType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ── Gestion upload CV ──
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $cvFile */
            $cvFile = $form->get('cvFile')->getData();

            if ($cvFile) {
                $newFilename = uniqid('cv_') . '.' . $cvFile->guessExtension();
                try {
                    $cvFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/cvs',
                        $newFilename
                    );
                    $demande->setCv($newFilename);  // 👈 stocke le nom en base
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'upload du CV. Veuillez réessayer.');
                    return $this->render('frontoffice/user/demandeResponsable.html.twig', [
                        'form' => $form,
                        'club' => $club,
                    ]);
                }
            }
            // ── fin gestion CV ──

            $demande->setClub($club);
            /** @var \App\Entity\User $user */
            $demande->setUser($user);
            $demande->setDateInscription(new \DateTime());
            $demande->setStatut(StatutDemande::en_attente);

            $entityManager->persist($demande);
            $entityManager->flush();

            $dispatcher->dispatch(new DemandeResponsableEvent($demande), DemandeResponsableEvent::NAME);

            $this->addFlash('success', 'Votre candidature a été envoyée à l\'administrateur.');
            return $this->redirectToRoute('user_mes_clubs');
        }

        return $this->render('frontoffice/user/demandeResponsable.html.twig', [
            'form' => $form,
            'club' => $club,
        ]);
    }
}