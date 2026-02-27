<?php

namespace App\Repository;

use App\Entity\ResultatQuiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ResultatQuiz>
 */
class ResultatQuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResultatQuiz::class);
    }

//    /**
//     * @return ResultatQuiz[] Returns an array of ResultatQuiz objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ResultatQuiz
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function userCanGetCertificate($user): bool
    {
        // nombre total de quiz
        $totalQuiz = $this->getEntityManager()
            ->getRepository(\App\Entity\Quiz::class)
            ->count([]);

        // résultats du user
        $resultats = $this->findBy(['user' => $user]);

        // si pas tous les quiz faits
        if (count($resultats) < $totalQuiz) {
            return false;
        }

        // vérifier note >= 70%
        foreach ($resultats as $resultat) {
            if ($resultat->getPercentage() < 70) {
                return false;
            }
        }

        return true;
    }
}
