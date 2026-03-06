<?php

namespace App\Repository;

use App\Entity\Formation;
use App\Entity\ResultatQuiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    /**
     * Returns a QueryBuilder for all ResultatQuiz that belong to quizzes
     * of the given formation, ordered by date DESC.
     * Used by KnpPaginator for paginated display.
     */
    public function findByFormationQB(Formation $formation): QueryBuilder
    {
        return $this->createQueryBuilder('r')
            ->join('r.quiz', 'q')
            ->andWhere('q.formation = :formation')
            ->setParameter('formation', $formation)
            ->orderBy('r.datePassage', 'DESC');
    }
}
