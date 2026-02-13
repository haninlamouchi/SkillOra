<?php

namespace App\Repository;

use App\Entity\Challenge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Challenge>
 */
class ChallengeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Challenge::class);
    }



      /**
     * Recherche avancée de challenges
     *
     * @param string|null $titre
     * @param \DateTimeInterface|null $dateDebutMin
     * @param \DateTimeInterface|null $dateFinMax
     * @return Challenge[]
     */
    public function searchChallenges(?string $titre, ?\DateTimeInterface $dateDebutMin, ?\DateTimeInterface $dateFinMax): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($titre) {
            $qb->andWhere('c.titre LIKE :titre')
               ->setParameter('titre', '%'.$titre.'%');
        }

        if ($dateDebutMin) {
            $qb->andWhere('c.dateDebut >= :dateDebutMin')
               ->setParameter('dateDebutMin', $dateDebutMin);
        }

        if ($dateFinMax) {
            $qb->andWhere('c.dateFin <= :dateFinMax')
               ->setParameter('dateFinMax', $dateFinMax);
        }

        $qb->orderBy('c.dateDebut', 'ASC'); // Tri par date de début

        return $qb->getQuery()->getResult();
    }
}




//    /**
//     * @return Challenge[] Returns an array of Challenge objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Challenge
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

