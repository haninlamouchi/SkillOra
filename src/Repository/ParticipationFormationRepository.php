<?php

namespace App\Repository;

use App\Entity\Formation;
use App\Entity\ParticipationFormation;
use App\Entity\User;
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
     * Find all participants for a given formation (with user data eagerly loaded).
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
    public function isAlreadyParticipating(User $user, Formation $formation): bool
    {
        return null !== $this->findOneBy([
            'user'      => $user,
            'formation' => $formation,
        ]);
    }

    /**
     * Return all formation IDs the given user participates in (for quick lookup).
     *
     * @return int[]
     */
    public function findParticipatedFormationIds(User $user): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('IDENTITY(p.formation) AS fid')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'fid');
    }
}
