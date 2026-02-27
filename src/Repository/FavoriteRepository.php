<?php

namespace App\Repository;

use App\Entity\Favorite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    // Check if formation is already favorite
    public function isFavorite($user, $formation): bool
    {
        return (bool) $this->createQueryBuilder('f')
            ->select('1')
            ->andWhere('f.user = :user')
            ->andWhere('f.formation = :formation')
            ->setParameter('user', $user)
            ->setParameter('formation', $formation)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // Return only formation IDs (optimized)
    public function findUserFavoritesIds($user): array
    {
        $results = $this->createQueryBuilder('f')
            ->select('IDENTITY(f.formation) as formation_id')
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getArrayResult();

        return array_column($results, 'formation_id');
    }
}