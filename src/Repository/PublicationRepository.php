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

    /**
     * Returns QueryBuilder for pagination support.
     *
     * @param string|null $search  Search term (matches titre, contenu, user name)
     * @param string      $sort    Column to sort by
     * @param string      $dir     ASC or DESC
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getPublicationsQueryBuilder(?string $search = null, string $sort = 'datePublication', string $dir = 'DESC')
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

        return $qb;
    }

    // ════════════════════════════════════════════
    //  TAGS
    // ════════════════════════════════════════════

    /**
     * Publications filtrées par tag (page /tags/{nom})
     */
    public function findByTag(string $tagNom): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.tags', 't')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->where('t.nom = :nom')
            ->andWhere('p.status = :status')
            ->setParameter('nom', strtolower($tagNom))
            ->setParameter('status', \App\Enum\StatusPublication::PUBLIE)
            ->orderBy('p.datePublication', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Toutes les publications PUBLIEES avec leurs tags (évite N+1)
     */
    public function findAllWithTags(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.tags', 't')
            ->leftJoin('p.user', 'u')
            ->addSelect('t', 'u')
            ->where('p.status = :status')
            ->setParameter('status', \App\Enum\StatusPublication::PUBLIE)
            ->orderBy('p.datePublication', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Publications similaires par tags communs (section "Voir aussi")
     */
    public function findSimilar(int $publicationId, array $tagIds, int $limit = 4): array
    {
        if (empty($tagIds)) return [];

        return $this->createQueryBuilder('p')
            ->join('p.tags', 't')
            ->where('t.id IN (:tagIds)')
            ->andWhere('p.id != :id')
            ->andWhere('p.status = :status')
            ->setParameter('tagIds', $tagIds)
            ->setParameter('id', $publicationId)
            ->setParameter('status', \App\Enum\StatusPublication::PUBLIE)
            ->groupBy('p.id')
            ->orderBy('COUNT(t.id)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}