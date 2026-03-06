<?php

namespace App\Controller;

use App\Repository\PublicationRepository;
use App\Repository\CommentaireRepository;
use App\Repository\ClubRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/responsable-club-stats')]
#[IsGranted('ROLE_RESPONSABLE_CLUB')]
class ResponsableClubController extends AbstractController
{
    #[Route('/dashboard_user', name: 'app_responsable_dashboard_user')]
    public function dashboard(
        PublicationRepository $publicationRepo,
        CommentaireRepository $commentaireRepo
    ): Response {
        $user = $this->getUser();

        return $this->render('responsable/dashboard.html.twig', [
            'user'              => $user,
            'currentUser'       => $user,   // ← LA LIGNE QUI MANQUAIT
            'totalPublications' => count($publicationRepo->findAll()),
            'enAttente'         => count($publicationRepo->findBy(['status' => \App\Enum\StatusPublication::EN_ATTENTE])),
            'publiees'          => count($publicationRepo->findBy(['status' => \App\Enum\StatusPublication::PUBLIE])),
            'totalCommentaires' => count($commentaireRepo->findAll()),
        ]);
    }

    #[Route('/forum-dashboard', name: 'app_responsable_forum_dashboard')]
    public function forumDashboard(
        EntityManagerInterface $em,
        ClubRepository $clubRepo
    ): Response {
        $user = $this->getUser();

        // Récupérer le club du responsable et les IDs des membres
        $club = $clubRepo->findOneBy(['responsable' => $user]);
        $memberIds = [];
        
        if ($club && $user instanceof \App\Entity\User) {
            foreach ($club->getMembres() as $membre) {
                $memberIds[] = $membre->getId();
            }
            // Ajouter le responsable lui-même
            $memberIds[] = $user->getId();
        } elseif ($user instanceof \App\Entity\User) {
            // Si pas de club, utiliser uniquement l'ID du responsable
            $memberIds[] = $user->getId();
        }

        // ────────────────────────────────────────────
        // 1. STATISTIQUES GÉNÉRALES (filtrées par club)
        // ────────────────────────────────────────────
        $totalPublications = (int) $em->createQuery(
            'SELECT COUNT(p.id) FROM App\Entity\Publication p 
             JOIN p.user u 
             WHERE u.id IN (:memberIds)'
        )->setParameter('memberIds', $memberIds)
          ->getSingleScalarResult();

        $totalCommentaires = (int) $em->createQuery(
            'SELECT COUNT(c.id) FROM App\Entity\Commentaire c 
             JOIN c.user u 
             WHERE u.id IN (:memberIds)'
        )->setParameter('memberIds', $memberIds)
          ->getSingleScalarResult();

        $publicationsEnAttente = (int) $em->createQuery(
            'SELECT COUNT(p.id) FROM App\Entity\Publication p 
             JOIN p.user u 
             WHERE p.status = :status AND u.id IN (:memberIds)'
        )->setParameter('status', \App\Enum\StatusPublication::EN_ATTENTE)
          ->setParameter('memberIds', $memberIds)
          ->getSingleScalarResult();

        $publicationsPubliees = (int) $em->createQuery(
            'SELECT COUNT(p.id) FROM App\Entity\Publication p 
             JOIN p.user u 
             WHERE p.status = :status AND u.id IN (:memberIds)'
        )->setParameter('status', \App\Enum\StatusPublication::PUBLIE)
          ->setParameter('memberIds', $memberIds)
          ->getSingleScalarResult();

        // Moyenne de commentaires par publication
        $avgCommentsPerPub = $totalPublications > 0 
            ? round($totalCommentaires / $totalPublications, 2) 
            : 0;

        // ────────────────────────────────────────────
        // 2. PUBLICATIONS PAR DATE (30 derniers jours, filtrées par club)
        // ────────────────────────────────────────────
        $dateLimit = new \DateTime('-30 days');
        $conn = $em->getConnection();
        
        $memberIdsString = implode(',', $memberIds);
        $pubsByDateSql = "
            SELECT DATE(date_publication) as pubDate, COUNT(id_Publication) as total
            FROM Publication
            WHERE date_publication >= :dateLimit 
              AND id_User IN ($memberIdsString)
            GROUP BY DATE(date_publication)
            ORDER BY DATE(date_publication) ASC
        ";
        $stmt = $conn->prepare($pubsByDateSql);
        $stmt->bindValue('dateLimit', $dateLimit->format('Y-m-d H:i:s'));
        $pubsByDate = $stmt->executeQuery()->fetchAllAssociative();

        // ────────────────────────────────────────────
        // 3. COMMENTAIRES PAR DATE (30 derniers jours, filtrés par club)
        // ────────────────────────────────────────────
        $commentsByDateSql = "
            SELECT DATE(date_commentaire) as comDate, COUNT(id_commentaire) as total
            FROM commentaire
            WHERE date_commentaire >= :dateLimit 
              AND id_User IN ($memberIdsString)
            GROUP BY DATE(date_commentaire)
            ORDER BY DATE(date_commentaire) ASC
        ";
        $stmt = $conn->prepare($commentsByDateSql);
        $stmt->bindValue('dateLimit', $dateLimit->format('Y-m-d H:i:s'));
        $commentsByDate = $stmt->executeQuery()->fetchAllAssociative();

        // ────────────────────────────────────────────
        // 4. PUBLICATIONS PAR STATUT
        // ────────────────────────────────────────────
        $pubsByStatus = [
            ['label' => 'En attente', 'count' => $publicationsEnAttente],
            ['label' => 'Publiées', 'count' => $publicationsPubliees]
        ];

        // ────────────────────────────────────────────
        // 5. PUBLICATIONS PAR TYPE (filtrées par club)
        // ────────────────────────────────────────────
        $pubsByType = $em->createQuery(
            'SELECT p.typecontenu as type, COUNT(p.id) as total
             FROM App\Entity\Publication p
             JOIN p.user u
             WHERE u.id IN (:memberIds)
             GROUP BY p.typecontenu'
        )->setParameter('memberIds', $memberIds)
          ->getResult();

        // Formater pour affichage
        $pubsByTypeFormatted = [];
        foreach ($pubsByType as $row) {
            $label = match($row['type']) {
                \App\Enum\TypeContenu::TEXTE => 'Texte',
                \App\Enum\TypeContenu::IMAGE => 'Image',
                \App\Enum\TypeContenu::VIDEO => 'Vidéo',
                default => 'Autre'
            };
            $pubsByTypeFormatted[] = ['label' => $label, 'count' => (int)$row['total']];
        }

        // ────────────────────────────────────────────
        // 6. TOP 5 PUBLICATIONS LES PLUS COMMENTÉES (filtrées par club)
        // ────────────────────────────────────────────
        $topPublications = $em->createQuery(
            'SELECT p.id, p.titre, p.datePublication, COUNT(c.id) as commentCount
             FROM App\Entity\Publication p
             JOIN p.user u
             LEFT JOIN p.commentaires c
             WHERE u.id IN (:memberIds)
             GROUP BY p.id, p.titre, p.datePublication
             ORDER BY commentCount DESC'
        )->setParameter('memberIds', $memberIds)
          ->setMaxResults(5)
          ->getResult();

        // ────────────────────────────────────────────
        // 7. PUBLICATIONS RÉCENTES (dernières 7 jours, filtrées par club)
        // ────────────────────────────────────────────
        $recentDateLimit = new \DateTime('-7 days');
        $recentPublications = (int) $em->createQuery(
            'SELECT COUNT(p.id) FROM App\Entity\Publication p 
             JOIN p.user u
             WHERE p.datePublication >= :dateLimit AND u.id IN (:memberIds)'
        )->setParameter('dateLimit', $recentDateLimit)
          ->setParameter('memberIds', $memberIds)
          ->getSingleScalarResult();

        // ────────────────────────────────────────────
        // 8. COMMENTAIRES RÉCENTS (derniers 7 jours, filtrés par club)
        // ────────────────────────────────────────────
        $recentCommentaires = (int) $em->createQuery(
            'SELECT COUNT(c.id) FROM App\Entity\Commentaire c 
             JOIN c.user u
             WHERE c.dateCommentaire >= :dateLimit AND u.id IN (:memberIds)'
        )->setParameter('dateLimit', $recentDateLimit)
          ->setParameter('memberIds', $memberIds)
          ->getSingleScalarResult();

        return $this->render('responsable/forum_dashboard.html.twig', [
            'user' => $user,
            'currentUser' => $user,
            'club' => $club,
            
            // KPIs
            'totalPublications' => $totalPublications,
            'totalCommentaires' => $totalCommentaires,
            'avgCommentsPerPub' => $avgCommentsPerPub,
            'recentPublications' => $recentPublications,
            'recentCommentaires' => $recentCommentaires,
            
            // Données pour graphiques
            'pubsByDate' => $pubsByDate,
            'commentsByDate' => $commentsByDate,
            'pubsByStatus' => $pubsByStatus,
            'pubsByType' => $pubsByTypeFormatted,
            'topPublications' => $topPublications,
        ]);
    }
}