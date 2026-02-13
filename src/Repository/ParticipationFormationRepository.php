<?php

namespace App\Repository;

use App\Entity\ParticipationFormation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParticipationFormation>
 */
class ParticipationFormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParticipationFormation::class);
    }

    /**
     * Find all participants for a given formation.
     *
     * @return ParticipationFormation[]
     */
    public function findByFormation(int $formationId): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.user', 'u')
            ->addSelect('u')
            ->andWhere('p.formation = :formationId')
            ->setParameter('formationId', $formationId)
            ->orderBy('p.dateParticipation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if a user already participates in a formation.
     */
    public function isAlreadyParticipating(int $userId, int $formationId): bool
    {
        $result = $this->createQueryBuilder('p')
            ->andWhere('p.user = :userId')
            ->andWhere('p.formation = :formationId')
            ->setParameter('userId', $userId)
            ->setParameter('formationId', $formationId)
            ->getQuery()
            ->getOneOrNullResult();

        return $result !== null;
    }
}
