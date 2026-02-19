<?php
// Exemples de Repositories à créer dans src/Repository/

// ===============================================
// UserRepository.php
// ===============================================

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    // Exemples de méthodes personnalisées
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.role = :role')
            ->setParameter('role', $role)
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findEtudiants(): array
    {
        return $this->findByRole('etudiant');
    }

    public function findResponsables(): array
    {
        return $this->findByRole('responsable_club');
    }
}

// ===============================================
// ClubRepository.php
// ===============================================

namespace App\Repository;

use App\Entity\Club;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ClubRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Club::class);
    }

    public function findByResponsable(int $responsableId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.responsable = :responsableId')
            ->setParameter('responsableId', $responsableId)
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveClubs(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.responsable IS NOT NULL')
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

// ===============================================
// DemandeAdhesionRepository.php
// ===============================================

namespace App\Repository;

use App\Entity\DemandeAdhesion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DemandeAdhesionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeAdhesion::class);
    }

    public function findPendingByClub(int $clubId): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.club = :clubId')
            ->setParameter('clubId', $clubId)
            ->orderBy('d.dateInscription', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('d.dateInscription', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

// ===============================================
// PublicationRepository.php
// ===============================================

namespace App\Repository;

use App\Entity\Publication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PublicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Publication::class);
    }

    public function findRecentPublications(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.datePublication', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.typecontenu = :type')
            ->setParameter('type', $type)
            ->orderBy('p.datePublication', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

// ===============================================
// CommentaireRepository.php
// ===============================================

namespace App\Repository;

use App\Entity\Commentaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommentaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire::class);
    }

    public function findByPublication(int $publicationId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.publication = :publicationId')
            ->setParameter('publicationId', $publicationId)
            ->orderBy('c.dateCommentaire', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

// ===============================================
// FormationRepository.php
// ===============================================

namespace App\Repository;

use App\Entity\Formation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    public function findActiveFormations(): array
    {
        $now = new \DateTime();
        return $this->createQueryBuilder('f')
            ->andWhere('f.dateFin >= :now OR f.dateFin IS NULL')
            ->setParameter('now', $now)
            ->orderBy('f.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByClub(int $clubId): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.club = :clubId')
            ->setParameter('clubId', $clubId)
            ->orderBy('f.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

// ===============================================
// QuizRepository.php
// ===============================================

namespace App\Repository;

use App\Entity\Quiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quiz::class);
    }

    public function findByFormation(int $formationId): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.formation = :formationId')
            ->setParameter('formationId', $formationId)
            ->getQuery()
            ->getResult();
    }
}

// ===============================================
// QuestionRepository.php
// ===============================================

namespace App\Repository;

use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }
}

// ===============================================
// OptionQuestionRepository.php
// ===============================================

namespace App\Repository;

use App\Entity\OptionQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OptionQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OptionQuestion::class);
    }
}

// ===============================================
// ResultatQuizRepository.php
// ===============================================

namespace App\Repository;

use App\Entity\ResultatQuiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ResultatQuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResultatQuiz::class);
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    public function findByQuiz(int $quizId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.quiz = :quizId')
            ->setParameter('quizId', $quizId)
            ->orderBy('r.score', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

// ===============================================
// ParticipationFormationRepository.php
// ===============================================

namespace App\Repository;

use App\Entity\ParticipationFormation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ParticipationFormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParticipationFormation::class);
    }
}

// ===============================================
// ChallengeRepository.php
// ===============================================

namespace App\Repository;

use App\Entity\Challenge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ChallengeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Challenge::class);
    }

    public function findActiveChallenges(): array
    {
        $now = new \DateTime();
        return $this->createQueryBuilder('c')
            ->andWhere('c.dateFin >= :now OR c.dateFin IS NULL')
            ->setParameter('now', $now)
            ->orderBy('c.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByClub(int $clubId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.club = :clubId')
            ->setParameter('clubId', $clubId)
            ->orderBy('c.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

// ===============================================
// GroupeRepository.php
// ===============================================

namespace App\Repository;

use App\Entity\Groupe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GroupeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Groupe::class);
    }
}

// ===============================================
// MembreGroupeRepository.php
// ===============================================

namespace App\Repository;

use App\Entity\MembreGroupe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MembreGroupeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MembreGroupe::class);
    }
}

// ===============================================
// ParticipationRepository.php
// ===============================================

namespace App\Repository;

use App\Entity\Participation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participation::class);
    }

    public function findByChallenge(int $challengeId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.challenge = :challengeId')
            ->setParameter('challengeId', $challengeId)
            ->orderBy('p.dateParticipation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

// ===============================================
// LivrableChallengeRepository.php
// ===============================================

namespace App\Repository;

use App\Entity\LivrableChallenge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LivrableChallengeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LivrableChallenge::class);
    }
}
