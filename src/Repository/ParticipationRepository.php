<?php

namespace App\Repository;

use App\Entity\Participation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Participation>
 */
class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participation::class);
    }

    public function searchParticipations(?string $challenge, ?string $groupe): array
{
    $qb = $this->createQueryBuilder('p')
        ->leftJoin('p.challenge', 'c')
        ->leftJoin('p.groupe', 'g')
        ->addSelect('c', 'g');

    if ($challenge) {
        $qb->andWhere('c.titre LIKE :challenge')
           ->setParameter('challenge', '%'.$challenge.'%');
    }

    if ($groupe) {
        $qb->andWhere('g.nomGroupe LIKE :groupe')
           ->setParameter('groupe', '%'.$groupe.'%');
    }

    return $qb->getQuery()->getResult();
}


//    /**
//     * @return Participation[] Returns an array of Participation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Participation
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
