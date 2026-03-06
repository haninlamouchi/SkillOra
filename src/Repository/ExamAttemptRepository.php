<?php

namespace App\Repository;

use App\Entity\ExamAttempt;
use App\Entity\Exam;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExamAttemptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExamAttempt::class);
    }

    public function findLatestForUser(Exam $exam, User $user): ?ExamAttempt
    {
        return $this->createQueryBuilder('a')
            ->where('a.exam = :exam')
            ->andWhere('a.user = :user')
            ->setParameter('exam', $exam)
            ->setParameter('user', $user)
            ->orderBy('a.startedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
