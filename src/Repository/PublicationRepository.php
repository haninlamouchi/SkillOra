<?php

namespace App\Repository;

use App\Entity\Publication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Publication>
 */
class PublicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Publication::class);
    }

    /**
     * Search publications by keyword + sort.
     *
     * @param string|null $search  Search term (matches titre, contenu, user name)
     * @param string      $sort    Column to sort by
     * @param string      $dir     ASC or DESC
     * @return Publication[]
     */
    public function searchAndSort(?string $search = null, string $sort = 'datePublication', string $dir = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u');

        if ($search && trim($search) !== '') {
            $qb->andWhere('p.titre LIKE :q OR p.contenu LIKE :q OR u.nom LIKE :q OR u.prenom LIKE :q')
               ->setParameter('q', '%' . trim($search) . '%');
        }

        // Whitelist allowed sort columns
        $allowedSorts = [
            'datePublication' => 'p.datePublication',
            'titre'           => 'p.titre',
            'auteur'          => 'u.nom',
            'status'          => 'p.status',
            'typecontenu'     => 'p.typecontenu',
            'id'              => 'p.id',
        ];

        $sortColumn = $allowedSorts[$sort] ?? 'p.datePublication';
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $qb->orderBy($sortColumn, $dir);

        return $qb->getQuery()->getResult();
    }
}