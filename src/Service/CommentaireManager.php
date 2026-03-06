<?php

namespace App\Service;

use App\Entity\Commentaire;

/**
 * Service métier — Validation des règles métier de Commentaire
 *
 * Règles testées :
 *  1. Le contenu est obligatoire
 *  2. Le contenu doit contenir au moins 5 caractères
 *  3. Le contenu ne peut pas dépasser 500 caractères
 *  4. La publication associée est obligatoire
 *  5. L'auteur (user) est obligatoire
 */
class CommentaireManager
{
    public function validate(Commentaire $commentaire): bool
    {
        // ── Règle 1 : Contenu obligatoire ────────────────────
        if (empty(trim($commentaire->getContenu() ?? ''))) {
            throw new \InvalidArgumentException('Le contenu du commentaire est obligatoire.');
        }

        // ── Règle 2 : Contenu minimum 5 chars ────────────────
        $contenu = $commentaire->getContenu() ?? '';
        if (mb_strlen(trim($contenu)) < 5) {
            throw new \InvalidArgumentException('Le commentaire doit contenir au moins 5 caractères.');
        }

        // ── Règle 3 : Contenu maximum 500 chars ──────────────
        if (mb_strlen(trim($contenu)) > 500) {
            throw new \InvalidArgumentException('Le commentaire ne peut pas dépasser 500 caractères.');
        }

        // ── Règle 4 : Publication obligatoire ────────────────
        if ($commentaire->getPublication() === null) {
            throw new \InvalidArgumentException('Une publication est requise pour le commentaire.');
        }

        // ── Règle 5 : User obligatoire ───────────────────────
        if ($commentaire->getUser() === null) {
            throw new \InvalidArgumentException('Un auteur est requis pour le commentaire.');
        }

        return true;
    }
}