<?php

namespace App\Service;

use App\Entity\User;

class UserManager
{
    public function validate(User $user): bool
    {
        // Règle 1 : Le nom est obligatoire
        if (empty($user->getNom())) {
            throw new \InvalidArgumentException('Le nom est obligatoire');
        }

        // Règle 2 : Le prénom est obligatoire
        if (empty($user->getPrenom())) {
            throw new \InvalidArgumentException('Le prénom est obligatoire');
        }

        // Règle 3 : L'email doit être valide
        if (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide');
        }

        // Règle 4 : Le téléphone doit avoir au moins 8 caractères
        if ($user->getTelephone() && strlen($user->getTelephone()) < 8) {
            throw new \InvalidArgumentException('Numéro de téléphone invalide');
        }

        // Règle 5 : La date de naissance doit être dans le passé
        if ($user->getDateNaissance() && $user->getDateNaissance() >= new \DateTime()) {
            throw new \InvalidArgumentException('La date de naissance doit être dans le passé');
        }

        return true;
    }
}