<?php

namespace App\Repository;

use App\Entity\Formation;
use App\Entity\ParticipationFormation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParticipationFormation>
 */
class ParticipationFormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParticipationFormation::class);
    }

    /**
     * Find all ParticipationFormation for a given formation using native SQL
     * to bypass Doctrine's DQL issue with User's custom PK column name (id_User).
     *
     * @return ParticipationFormation[]
     */
    public function findByFormation(int $formationId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT p.id
            FROM participation_formation p
            WHERE p.formation_id = :formationId
            ORDER BY p.date_participation DESC
        ';

        $ids = $conn->executeQuery($sql, ['formationId' => $formationId])
                    ->fetchFirstColumn();

        if (empty($ids)) {
            return [];
        }

        // Load full entities one by one — safe because User PK is handled by Doctrine's
        // basic find(), which uses the correct column name internally.
        $results = [];
        foreach ($ids as $id) {
            $p = $this->find($id);
            if ($p) {
                $results[] = $p;
            }
        }

        return $results;
    }

    /**
     * Check if a user already participates in a formation.
     */
    public function isAlreadyParticipating(User $user, Formation $formation): bool
    {
        return null !== $this->findOneBy([
            'user'      => $user,
            'formation' => $formation,
        ]);
    }

    /**
     * Check if a user already has a PAID participation for a formation.
     */
    public function hasPaidParticipation(User $user, Formation $formation): bool
    {
        return null !== $this->findOneBy([
            'user'          => $user,
            'formation'     => $formation,
            'paymentStatus' => true,
        ]);
    }

    /**
     * Return all formation IDs the given user participates in (for quick lookup).
     *
     * @return int[]
     */
    public function findParticipatedFormationIds(User $user): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('IDENTITY(p.formation) AS fid')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getScalarResult();

        return array_map(fn($r) => (int) $r['fid'], $rows);
    }
}