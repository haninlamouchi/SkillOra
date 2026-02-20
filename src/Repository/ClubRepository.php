<?php

namespace App\Repository;

use App\Entity\Club;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Club>
 */
class ClubRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Club::class);
    }

    /**
     * Find the club managed by a responsable_club user.
     */
    public function findByResponsable(User $responsable): ?Club
    {
        return $this->findOneBy(['responsable' => $responsable]);
    }

    /**
     * All clubs the student is a member of.
     *
     * @return Club[]
     */
    public function findByMembre(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.membres', 'm')
            ->andWhere('m = :user')
            ->setParameter('user', $user)
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
