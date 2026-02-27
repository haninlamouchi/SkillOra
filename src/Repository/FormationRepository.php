<?php

namespace App\Repository;

use App\Entity\Formation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    // ─────────────────────────────────────────────
    // ETUDIANT
    // ─────────────────────────────────────────────

    public function findByParticipant(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.participations', 'p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }


    public function findActiveByParticipant(User $user): array
    {
        $today = new \DateTime('today');

        return $this->createQueryBuilder('f')
            ->innerJoin('f.participations', 'p')
            ->andWhere('p.user = :user')
            ->andWhere('f.dateDebut <= :today')
            ->andWhere('f.dateFin >= :today')
            ->setParameter('user', $user)
            ->setParameter('today', $today)
            ->orderBy('f.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ─────────────────────────────────────────────
    // CLUBS DE L'ETUDIANT
    // Utilise une sous-requête DQL pour éviter le problème
    // de PK personnalisée id_Club dans le JOIN ManyToMany
    // ─────────────────────────────────────────────

    public function findByStudentClubs(User $user): array
    {
        // Récupérer les IDs des clubs de l'étudiant via ses participations
        $clubIds = $this->getEntityManager()
            ->createQuery('
                SELECT IDENTITY(p.formation) 
                FROM App\Entity\ParticipationFormation p
                WHERE p.user = :user
            ')
            ->setParameter('user', $user)
            ->getSingleColumnResult();

        // Approche alternative : passer par les clubs directement via DQL natif
        // en utilisant la relation Club->formations qui existe
        $em = $this->getEntityManager();
        
        // Récupérer les clubs dont l'user est membre via SQL brut avec les vraies colonnes
        $conn = $em->getConnection();
        
        // D'abord on récupère la structure réelle de club_membre
        $columns = $conn->executeQuery('DESCRIBE club_membre')->fetchAllAssociative();
        $colNames = array_column($columns, 'Field');
        
        // Déterminer les vrais noms de colonnes
        $clubCol = in_array('club_id', $colNames) ? 'club_id' : (in_array('club_id_Club', $colNames) ? 'club_id_Club' : $colNames[0]);
        $userCol = in_array('user_id', $colNames) ? 'user_id' : (in_array('user_id_User', $colNames) ? 'user_id_User' : $colNames[1]);

        $sql = "
            SELECT DISTINCT f.id
            FROM formation f
            INNER JOIN club c ON f.club_id = c.id_Club
            INNER JOIN club_membre cm ON cm.{$clubCol} = c.id_Club
            WHERE cm.{$userCol} = ?
            ORDER BY f.date_debut DESC
        ";

        $ids = $conn->executeQuery($sql, [$user->getId()])->fetchFirstColumn();

        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('f')
            ->andWhere('f.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('f.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function findActiveByStudentClubs(User $user): array
    {
        $today = new \DateTime('today');
        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        $columns = $conn->executeQuery('DESCRIBE club_membre')->fetchAllAssociative();
        $colNames = array_column($columns, 'Field');
        $clubCol = in_array('club_id', $colNames) ? 'club_id' : (in_array('club_id_Club', $colNames) ? 'club_id_Club' : $colNames[0]);
        $userCol = in_array('user_id', $colNames) ? 'user_id' : (in_array('user_id_User', $colNames) ? 'user_id_User' : $colNames[1]);

        $sql = "
            SELECT DISTINCT f.id
            FROM formation f
            INNER JOIN club c ON f.club_id = c.id_Club
            INNER JOIN club_membre cm ON cm.{$clubCol} = c.id_Club
            WHERE cm.{$userCol} = ?
              AND f.date_debut <= ?
              AND f.date_fin >= ?
            ORDER BY f.date_fin ASC
        ";

        $ids = $conn->executeQuery($sql, [
            $user->getId(),
            $today->format('Y-m-d'),
            $today->format('Y-m-d'),
        ])->fetchFirstColumn();

        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('f')
            ->andWhere('f.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('f.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ─────────────────────────────────────────────
    // ADMIN
    // ─────────────────────────────────────────────

    public function findByAdminFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('f')
            ->leftJoin('f.club', 'c')
            ->leftJoin('c.responsable', 'r')
            ->addSelect('c', 'r');

        if (!empty($filters['keyword'])) {
            $qb->andWhere('f.titre LIKE :keyword OR f.description LIKE :keyword')
               ->setParameter('keyword', '%' . $filters['keyword'] . '%');
        }

        if (!empty($filters['club_id'])) {
            $qb->andWhere('c.id_Club = :clubId')
               ->setParameter('clubId', $filters['club_id']);
        }

        if (!empty($filters['responsable'])) {
            $qb->andWhere('r.id_User = :responsable')
               ->setParameter('responsable', $filters['responsable']);
        }

        if (!empty($filters['date_from'])) {
            $qb->andWhere('f.dateDebut >= :dateFrom')
               ->setParameter('dateFrom', new \DateTime($filters['date_from']));
        }

        if (!empty($filters['date_to'])) {
            $qb->andWhere('f.dateDebut <= :dateTo')
               ->setParameter('dateTo', new \DateTime($filters['date_to']));
        }

        return $qb->orderBy('f.dateDebut', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    // ─────────────────────────────────────────────
    // RESPONSABLE
    // ─────────────────────────────────────────────

    public function findByClub(int $clubId): array
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.club', 'c')
            ->andWhere('c.id_Club = :clubId')
            ->setParameter('clubId', $clubId)
            ->orderBy('f.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
