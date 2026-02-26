<?php

namespace App\Repository;

use App\Entity\Groupe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Groupe>
 */
class GroupeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Groupe::class);
    }

    public function searchGroupes(?string $nomGroupe, ?string $membre): array
    {  
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.membres', 'm')
            ->leftJoin('m.user', 'u')
            ->addSelect('m','u');

        if ($nomGroupe) {
            $qb->andWhere('g.nomGroupe LIKE :nom')
                ->setParameter('nom', '%'.$nomGroupe.'%');
        }

        if ($membre) {
            $qb->andWhere('u.nom LIKE :membre')
                ->setParameter('membre', '%'.$membre.'%');
    }

    return $qb->getQuery()->getResult();
}


//    /**
//     * @return Groupe[] Returns an array of Groupe objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('g.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Groupe
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
