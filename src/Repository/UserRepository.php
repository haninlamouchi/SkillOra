<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function searchUsers(string $query): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.nom LIKE :q OR u.prenom LIKE :q OR u.email LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('u.dateInscription', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function searchUsersQuery(string $query): \Doctrine\ORM\Query
    {
        return $this->createQueryBuilder('u')
            ->where('u.nom LIKE :q OR u.prenom LIKE :q OR u.email LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('u.dateInscription', 'DESC')
            ->getQuery();
    }
}
