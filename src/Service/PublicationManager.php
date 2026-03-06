<?php

namespace App\Service;

use App\Entity\Publication;
use App\Enum\StatusPublication;

/**
 * Service métier — Validation des règles métier de Publication
 *
 * Règles testées :
 *  1. Le titre est obligatoire
 *  2. Le titre doit contenir entre 3 et 200 caractères
 *  3. Le contenu est obligatoire
 *  4. Le contenu doit contenir au minimum 10 caractères
 *  5. Le statut doit être une valeur valide de StatusPublication
 */
class PublicationManager
{
    public function validate(Publication $publication): bool
    {
        // ── Règle 1 : Titre obligatoire ──────────────────────
        if (empty(trim($publication->getTitre() ?? ''))) {
            throw new \InvalidArgumentException('Le titre est obligatoire.');
        }

        // ── Règle 2 : Longueur titre (3-200 chars) ───────────
        $titreLongueur = mb_strlen(trim($publication->getTitre() ?? ''));
        if ($titreLongueur < 3) {
            throw new \InvalidArgumentException('Le titre doit contenir au moins 3 caractères.');
        }
        if ($titreLongueur > 200) {
            throw new \InvalidArgumentException('Le titre ne peut pas dépasser 200 caractères.');
        }

        // ── Règle 3 : Contenu obligatoire ────────────────────
        if (empty(trim($publication->getContenu() ?? ''))) {
            throw new \InvalidArgumentException('Le contenu est obligatoire.');
        }

        // ── Règle 4 : Contenu minimum 10 chars ───────────────
        if (mb_strlen(trim($publication->getContenu() ?? '')) < 10) {
            throw new \InvalidArgumentException('Le contenu doit contenir au moins 10 caractères.');
        }

        // ── Règle 5 : Statut valide ───────────────────────────
        if ($publication->getStatus() === null) {
            throw new \InvalidArgumentException('Le statut est obligatoire.');
        }

        if (!($publication->getStatus() instanceof StatusPublication)) {
            throw new \InvalidArgumentException('Le statut est invalide.');
        }

        return true;
    }
}