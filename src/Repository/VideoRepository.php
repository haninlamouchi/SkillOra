<?php

namespace App\Repository;

use App\Entity\Video;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Video>
 */
class VideoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Video::class);
    }

    /**
     * @return Video[]
     */
    public function findByFormation(int $formationId): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.formation = :formationId')
            ->setParameter('formationId', $formationId)
            ->orderBy('v.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
