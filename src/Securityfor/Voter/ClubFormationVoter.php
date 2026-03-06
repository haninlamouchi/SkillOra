<?php

namespace App\Security\Voter;

use App\Entity\Formation;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for formation-level access control.
 *
 * Supported attributes:
 *  - FORMATION_EDIT   : responsable can only edit formations of his own club
 *  - FORMATION_DELETE : same scope as EDIT
 *  - FORMATION_VIEW   : responsable or etudiant scoped to their club
 */
class ClubFormationVoter extends Voter
{
    public const EDIT   = 'FORMATION_EDIT';
    public const DELETE = 'FORMATION_DELETE';
    public const VIEW   = 'FORMATION_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Formation
            && in_array($attribute, [self::EDIT, self::DELETE, self::VIEW], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Formation $formation */
        $formation = $subject;

        return match ($attribute) {
            self::EDIT, self::DELETE => $this->canManage($user, $formation),
            self::VIEW               => $this->canView($user, $formation),
            default                  => false,
        };
    }

    /**
     * A responsable_club can manage (edit/delete) only formations of his own club.
     */
    private function canManage(User $user, Formation $formation): bool
    {
        if (!in_array('ROLE_RESPONSABLE_CLUB', $user->getRoles(), true)) {
            return false;
        }

        $myClub = $user->getClubs()->first() ?: null;

        return $myClub !== null
            && $formation->getClub() !== null
            && $myClub->getId() === $formation->getClub()->getId();
    }

    /**
     * An etudiant can view only formations from clubs he joined.
     * A responsable can view formations of his own club.
     */
    private function canView(User $user, Formation $formation): bool
    {
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        if (in_array('ROLE_RESPONSABLE_CLUB', $user->getRoles(), true)) {
            return $this->canManage($user, $formation);
        }

        if (in_array('ROLE_MEMBRE', $user->getRoles(), true)) {
            $clubIds = array_map(
                fn($c) => $c->getId(),
                $user->getClubs()->toArray()
            );

            return $formation->getClub() !== null
                && in_array($formation->getClub()->getId(), $clubIds, true);
        }

        return false;
    }
}
