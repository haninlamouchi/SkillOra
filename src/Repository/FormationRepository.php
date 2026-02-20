<?php

namespace App\Repository;

use App\Entity\Formation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Formation>
 */
class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  ETUDIANT QUERIES
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Formations the student participates in (regardless of dates).
     *
     * @return Formation[]
     */
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

    /**
     * Formations the student participates in AND that are currently active.
     *
     * @return Formation[]
     */
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

    /**
     * All formations from clubs the student has joined.
     * Filters to only show formations of clubs the student is a member of.
     *
     * @return Formation[]
     */
    public function findByStudentClubs(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.club', 'c')
            ->innerJoin('c.membres', 'm')
            ->andWhere('m = :user')
            ->setParameter('user', $user)
            ->orderBy('f.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Active formations from clubs the student has joined.
     *
     * @return Formation[]
     */
    public function findActiveByStudentClubs(User $user): array
    {
        $today = new \DateTime('today');

        return $this->createQueryBuilder('f')
            ->innerJoin('f.club', 'c')
            ->innerJoin('c.membres', 'm')
            ->andWhere('m = :user')
            ->andWhere('f.dateDebut <= :today')
            ->andWhere('f.dateFin >= :today')
            ->setParameter('user', $user)
            ->setParameter('today', $today)
            ->orderBy('f.dateFin', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  RESPONSABLE QUERIES
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * All formations belonging to a specific club.
     *
     * @return Formation[]
     */
    public function findByClub(int $clubId): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.club = :clubId')
            ->setParameter('clubId', $clubId)
            ->orderBy('f.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
