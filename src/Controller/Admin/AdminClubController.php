<?php

namespace App\Controller\Admin;

use App\Entity\Club;
use App\Entity\DemandeClub;
use App\Entity\DemandeAdhesion;
use App\Enum\StatutDemande;
use App\Form\ClubType;
use App\Form\DemandeClubType;
use App\Repository\ClubRepository;
use App\Repository\DemandeAdhesionRepository;
use App\Repository\DemandeClubRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/club')]
class AdminClubController extends AbstractController
{
    #[Route('/', name: 'admin_club_index', methods: ['GET'])]
    public function index(Request $request, ClubRepository $clubRepository): Response
    {
        return $this->render('backoffice/admin/index.html.twig', [
            'clubs' => $clubRepository->findAll(),
        ]);
    }

    // ---- Création d'un club directement par l'admin ----
    #[Route('/new', name: 'admin_club_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $club = new Club();
        $form = $this->createForm(ClubType::class, $club);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($club);
            $entityManager->flush();

            $this->addFlash('success', 'Club créé avec succès.');
            return $this->redirectToRoute('admin_club_index');
        }

        return $this->render('backoffice/admin/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/demandes', name: 'admin_club_demandes', methods: ['GET'])]
    public function demandes(DemandeClubRepository $demandeClubRepository): Response
    {
        return $this->render('backoffice/admin/indexDemande.html.twig', [
            'demandes' => $demandeClubRepository->findAll(),
        ]);
    }

    #[Route('/adhesions', name: 'admin_adhesion_index', methods: ['GET'])]
    public function adhesions(DemandeAdhesionRepository $repo): Response
    {
        return $this->render('backoffice/admin/indexAdhesion.html.twig', [
            'dem' => $repo->findBy(['statut' => StatutDemande::en_attente]),
        ]);
    }

    #[Route('/demande/{id}/accepter', name: 'admin_club_accepter', methods: ['POST'])]
    public function accepter(DemandeClub $demande, EntityManagerInterface $entityManager): Response
    {
        $club = new Club();
        $club->setNom($demande->getNom());
        $club->setDescription($demande->getDescription());
        $club->setLogo($demande->getLogo());
        $club->setDateCreation($demande->getDateCreation());
        $club->setSiteWeb($demande->getSiteWeb());
        $club->setResponsable($demande->getResponsable());

        $entityManager->persist($club);
        $entityManager->remove($demande);
        $entityManager->flush();

        $this->addFlash('success', 'Demande acceptée, club créé avec succès.');
        return $this->redirectToRoute('admin_club_demandes');
    }

    #[Route('/demande/{id}/refuser', name: 'admin_club_refuser', methods: ['POST'])]
    public function refuser(DemandeClub $demande, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($demande);
        $entityManager->flush();

        $this->addFlash('warning', 'Demande refusée et supprimée.');
        return $this->redirectToRoute('admin_club_demandes');
    }

    #[Route('/adhesion/{id}/accepter', name: 'admin_adhesion_accepter', methods: ['POST'])]
    public function accepterResponsable(DemandeAdhesion $demande, EntityManagerInterface $entityManager): Response
    {
        $club = $demande->getClub();
        $club->setResponsable($demande->getUser());
        $entityManager->persist($club);
        $entityManager->flush();

        $this->addFlash('success', 'L\'utilisateur est maintenant responsable du club.');
        return $this->redirectToRoute('admin_adhesion_index');
    }

    #[Route('/adhesion/{id}/refuser', name: 'admin_adhesion_refuser', methods: ['POST'])]
    public function refuserResponsable(DemandeAdhesion $demande, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($demande);
        $entityManager->flush();

        $this->addFlash('warning', 'Demande de responsable refusée.');
        return $this->redirectToRoute('admin_adhesion_index');
    }

    // ---- routes dynamiques EN DERNIER ----

    #[Route('/{id}', name: 'admin_club_show', methods: ['GET'])]
    public function show(Club $club): Response
    {
        return $this->render('backoffice/admin/show.html.twig', [
            'club' => $club,
        ]);
    }

    #[Route('/{id}', name: 'admin_club_delete', methods: ['POST'])]
    public function delete(Request $request, Club $club, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$club->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($club);
            $entityManager->flush();
            $this->addFlash('success', 'Le club a été supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_club_index', [], Response::HTTP_SEE_OTHER);
    }
}