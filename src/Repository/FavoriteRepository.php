<?php

namespace App\Repository;

use App\Entity\Favorite;
use App\Entity\Formation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    /**
     * Returns an array of formation IDs that are favorited by the given user.
     *
     * @return int[]
     */
    public function findUserFavoritesIds(User $user): array
    {
        $rows = $this->createQueryBuilder('f')
            ->select('IDENTITY(f.formation) AS fid')
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'fid');
    }

    /**
     * Find a single Favorite row for a user+formation pair.
     */
    public function findByUserAndFormation(User $user, Formation $formation): ?Favorite
    {
        /** @var Favorite|null $result */
        $result = $this->findOneBy([
            'user'      => $user,
            'formation' => $formation,
        ]);
        
        return $result;
    }
}
