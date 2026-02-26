<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Retourne les notifications non lues d'un utilisateur
     */
    public function findNonLuesByUser(int $userId): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.destinataire = :userId')
            ->andWhere('n.isLu = :isLu')
            ->setParameter('userId', $userId)
            ->setParameter('isLu', false)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne toutes les notifications d'un utilisateur
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.destinataire = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
