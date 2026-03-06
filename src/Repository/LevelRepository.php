<?php

namespace App\Repository;

use App\Entity\Level;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Level>
 */
class LevelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Level::class);
    }

    /**
     * @return Level[]
     */
    public function findByFormation(int $formationId): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.formation = :fid')
            ->setParameter('fid', $formationId)
            ->orderBy('l.numero', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
