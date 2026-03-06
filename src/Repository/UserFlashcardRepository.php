<?php

namespace App\Repository;

use App\Entity\UserFlashcard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserFlashcardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserFlashcard::class);
    }

    public function countKnownByUserAndFormation(\App\Entity\User $user, \App\Entity\Formation $formation): int
    {
        $result = $this->createQueryBuilder('uf')
            ->join('uf.flashcard', 'f')
            ->where('uf.user = :user')
            ->andWhere('f.formation = :formation')
            ->andWhere('uf.known = true')
            ->setParameter('user', $user)
            ->setParameter('formation', $formation)
            ->select('COUNT(uf.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        return (int) $result;
    }
}