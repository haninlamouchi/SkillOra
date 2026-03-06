<?php

namespace App\Controller\Admin;

use App\Entity\Club;
use App\Entity\DemandeClub;
use App\Entity\DemandeAdhesion;
use App\Enum\StatutDemande;
use App\Entity\DemandeMembre;
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
        $club->setNom($demande->getNom() ?? '');
        $club->setDescription($demande->getDescription());
        $club->setLogo($demande->getLogo());
        $club->setDateCreation($demande->getDateCreation());
        $club->setSiteWeb($demande->getSiteWeb());
        $club->setResponsable($demande->getResponsable());

         // récupérer le user demandeur
        $user = $demande->getResponsable();
        
        if (!$user) {
            $this->addFlash('error', 'Erreur : aucun responsable associé à cette demande.');
            return $this->redirectToRoute('admin_club_demandes');
        }

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
    $club               = $demande->getClub();
    $nouveauResponsable = $demande->getUser();
    
    if (!$club || !$nouveauResponsable) {
        $this->addFlash('error', 'Erreur : club ou utilisateur introuvable.');
        return $this->redirectToRoute('admin_club_adhesions');
    }
    
    $ancienResponsable  = $club->getResponsable();

    if ($ancienResponsable) {
        // 1. Supprimer l'ancienne demande acceptée de l'ancien responsable
        $ancienneDemande = $entityManager->getRepository(DemandeAdhesion::class)
            ->findOneBy([
                'club'   => $club,
                'user'   => $ancienResponsable,
                'statut' => StatutDemande::accepte,
            ]);

        if ($ancienneDemande) {
            $entityManager->remove($ancienneDemande);
        }

        // 2. Déterminer le nouveau rôle de l'ancien responsable
        $autreClub = $entityManager->getRepository(Club::class)
            ->findOneBy(['responsable' => $ancienResponsable]);

        if ($autreClub && $autreClub->getId() !== $club->getId()) {
            // Il est responsable d'un autre club → on ne touche pas son rôle
        } else {
            $membreAutreClub = $entityManager->getRepository(DemandeMembre::class)
                ->findOneBy([
                    'user'   => $ancienResponsable,
                    'statut' => StatutDemande::accepte,
                ]);

            $ancienResponsable->setRole($membreAutreClub ? 'membre' : 'etudiant');
            $entityManager->persist($ancienResponsable);
        }
    } // ← fermeture du if($ancienResponsable)

    // 3. Nouveau responsable
    $nouveauResponsable->setRole('responsable_club');
    $entityManager->persist($nouveauResponsable);

    // 4. Mettre à jour le club
    $club->setResponsable($nouveauResponsable);
    $entityManager->persist($club);

    // 5. Marquer la demande comme acceptée
    $demande->setStatut(StatutDemande::accepte);
    $entityManager->persist($demande);

    $entityManager->flush();

    // SMS
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
} // ← fermeture de la méthode

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
public function delete(
    Request $request,
    Club $club,
    EntityManagerInterface $entityManager
): Response {
    if ($this->isCsrfTokenValid('delete' . $club->getId(), $request->getPayload()->getString('_token'))) {

        // 1. Remettre le responsable en étudiant
        $responsable = $club->getResponsable();
        if ($responsable !== null) {
            $responsable->setRole('etudiant');
            $entityManager->persist($responsable);
        }

        // 2. Supprimer les DemandeAdhesion liées (demande de responsable)
        $demandesAdhesion = $entityManager->getRepository(DemandeAdhesion::class)
            ->findBy(['club' => $club]);
        foreach ($demandesAdhesion as $demande) {
            $entityManager->remove($demande);
        }

        // 3. Supprimer les DemandeMembre liées (demande de membre)
        $demandesMembre = $entityManager->getRepository(DemandeMembre::class)
            ->findBy(['club' => $club]);
        foreach ($demandesMembre as $demande) {
            $entityManager->remove($demande);
        }

        // 4. Supprimer le club
        $entityManager->remove($club);
        $entityManager->flush();

        $this->addFlash('success', 'Le club a été supprimé avec succès.');
    }

    return $this->redirectToRoute('admin_club_index', [], Response::HTTP_SEE_OTHER);
}
}