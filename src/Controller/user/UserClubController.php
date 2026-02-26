<?php

namespace App\Controller\user;

use App\Entity\Club;
use App\Entity\DemandeAdhesion;
use App\Entity\DemandeMembre;
use App\Entity\DemandeClub;
use App\Form\DemandeClubType;
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
#[Route('/user/club')]
class UserClubController extends AbstractController
{
    // ⚠️ Routes statiques EN PREMIER

    // Liste tous les clubs
    #[Route('/', name: 'user_club_index', methods: ['GET'])]
public function index(ClubRepository $clubRepository,PaginatorInterface $paginator,Request $request): Response
{
    $user = $this->getUser();

    // Si l'utilisateur est responsable d'un club, on exclut ce club de la liste
    $query= $clubRepository->createQueryBuilder('c')
        ->where('c.responsable != :user OR c.responsable IS NULL')
        ->setParameter('user', $user)
        ->getQuery();
        // ✅ Pagination
        $clubs = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        6
        );

    return $this->render('frontoffice/user/indexMbreC.html.twig', [
        'clubs' => $clubs,
    ]);
}

    // Liste des clubs dont l'user est membre
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

    // Demander la création d'un nouveau club
    #[Route('/new', name: 'user_club_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager,EventDispatcherInterface $dispatcher): Response
    {
        $demande = new DemandeClub();
        $form = $this->createForm(DemandeClubType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $demande->setResponsable($this->getUser());
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

    // ⚠️ Routes dynamiques EN DERNIER

    // Demander de devenir membre d'un club
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
        $demande->setUser($user);
        $demande->setDateInscription(new \DateTime());
        $demande->setStatut(StatutDemande::en_attente);

        $entityManager->persist($demande);
        $entityManager->flush();

        $dispatcher->dispatch(new DemandeMembreEvent($demande), DemandeMembreEvent::NAME);


        $this->addFlash('success', 'Votre demande d\'adhésion a été envoyée au responsable.');
        return $this->redirectToRoute('user_club_index');
    }

    // Demander de devenir responsable d'un club
    #[Route('/{id}/responsable', name: 'user_club_responsable', methods: ['POST'])]
    public function responsable(
        Club $club,
        EntityManagerInterface $entityManager,
        DemandeMembreRepository $membreRepo,
        DemandeAdhesionRepository $adhesionRepo,
        EventDispatcherInterface $dispatcher
    ): Response {
        $user = $this->getUser();

        $dejaMembre = $membreRepo->findOneBy([
            'club'   => $club,
            'user'   => $user,
            'statut' => StatutDemande::accepte,
        ]);

        if (!$dejaMembre) {
            $this->addFlash('danger', 'Vous devez être membre du club pour demander à en devenir responsable.');
            return $this->redirectToRoute('user_club_index');
        }

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
        $demande->setClub($club);
        $demande->setUser($user);
        $demande->setDateInscription(new \DateTime());
        $demande->setStatut(StatutDemande::en_attente);

        $entityManager->persist($demande);
        $entityManager->flush();
        $dispatcher->dispatch(new DemandeResponsableEvent($demande), DemandeResponsableEvent::NAME);

        $this->addFlash('success', 'Votre demande pour devenir responsable a été envoyée à l\'admin.');
        return $this->redirectToRoute('user_mes_clubs');
    }
}