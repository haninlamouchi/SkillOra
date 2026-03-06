<?php

namespace App\Repository;

use App\Entity\Commentaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commentaire>
 */
class CommentaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire::class);
    }

    /**
     * Search commentaires by keyword + sort.
     *
     * @param string|null $search  Search term (matches contenu, user name, publication title)
     * @param string      $sort    Column to sort by
     * @param string      $dir     ASC or DESC
     * @return Commentaire[]
     */
    public function searchAndSort(?string $search = null, string $sort = 'dateCommentaire', string $dir = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->addSelect('u')
            ->leftJoin('c.publication', 'p')
            ->addSelect('p');

        if ($search && trim($search) !== '') {
            $qb->andWhere('c.contenu LIKE :q OR u.nom LIKE :q OR u.prenom LIKE :q OR p.titre LIKE :q')
               ->setParameter('q', '%' . trim($search) . '%');
        }

        $allowedSorts = [
            'dateCommentaire' => 'c.dateCommentaire',
            'auteur'          => 'u.nom',
            'publication'     => 'p.titre',
            'id'              => 'c.id',
        ];

        $sortColumn = $allowedSorts[$sort] ?? 'c.dateCommentaire';
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $qb->orderBy($sortColumn, $dir);

        return $qb->getQuery()->getResult();
    }
}