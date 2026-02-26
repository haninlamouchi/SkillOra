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
use App\Service\SmsService;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/club')]
class AdminClubController extends AbstractController
{
    #[Route('/', name: 'admin_club_index', methods: ['GET'])]
    public function index(
        Request $request,
        ClubRepository $clubRepository,
        PaginatorInterface $paginator  // 👈 ajouter
    ): Response {

        $query = $clubRepository->createQueryBuilder('c')
            ->orderBy('c.nom', 'ASC')
            ->getQuery(); // 👈 sans getResult()

        // ✅ Pagination
        $clubs = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10  // 10 clubs par page
        );

        return $this->render('backoffice/admin/index.html.twig', [
            'clubs' => $clubs,
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
    public function accepter(DemandeClub $demande, EntityManagerInterface $entityManager,SmsService $smsService): Response
    {
        $club = new Club();
        $club->setNom($demande->getNom());
        $club->setDescription($demande->getDescription());
        $club->setLogo($demande->getLogo());
        $club->setDateCreation($demande->getDateCreation());
        $club->setSiteWeb($demande->getSiteWeb());
        $club->setResponsable($demande->getResponsable());

         // récupérer le user demandeur
        $user = $demande->getResponsable();

        // changer son rôle
        $user->setRole('responsable_club');

        // persist user (bonne pratique)
        $entityManager->persist($user);
        $entityManager->persist($club);
        $entityManager->remove($demande);
        $entityManager->flush();

        // ✅ SMS au créateur du club
        if ($user->getTelephone()) {
            $smsService->send(
                $user->getTelephone(),
                '🎉 Félicitations ' . $user->getNom() . ' ! ' .
                'Votre demande de création du club ' .
                $demande->getNom() .
                ' a été acceptée. Vous êtes maintenant responsable !'
            );
        }

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
    public function accepterResponsable(
        DemandeAdhesion $demande,
        EntityManagerInterface $entityManager,
        SmsService $smsService
    ): Response {
        $club              = $demande->getClub();
        $ancienResponsable = $club->getResponsable();
        $nouveauResponsable = $demande->getUser();

        //  Changer le rôle de l'ancien responsable → membre
        if ($ancienResponsable) {
            $ancienResponsable->setRole('membre');
            $entityManager->persist($ancienResponsable);
        }

        //  Changer le rôle du nouveau responsable → responsable_club
        $nouveauResponsable->setRole('responsable_club');
        $entityManager->persist($nouveauResponsable);

        //  Changer le responsable du club
        $club->setResponsable($nouveauResponsable);
        $entityManager->persist($club);

        // Marquer la demande comme acceptée
        $demande->setStatut(StatutDemande::accepte);
        $entityManager->persist($demande);
        $entityManager->flush();

        // ✅ SMS au nouveau responsable
        if ($nouveauResponsable->getTelephone()) {
            $smsService->send(
                $nouveauResponsable->getTelephone(),
                '🎉 Félicitations ' . $nouveauResponsable->getNom() . ' ! ' .
                'Vous êtes maintenant responsable du club ' .
                $club->getNom() . '. Bonne chance !'
            );
        }

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