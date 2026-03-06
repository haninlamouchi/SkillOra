<?php

namespace App\Service;

use App\Entity\Formation;

/**
 * Pure business-rule validator for Formation.
 * No database access — safe to unit-test without mocks.
 */
class FormationValidatorService
{
    /**
     * Rule 1 – Titre must be non-blank and at most 200 characters.
     */
    public function isTitreValid(Formation $formation): bool
    {
        $titre = $formation->getTitre();
        return $titre !== null && $titre !== '' && strlen($titre) <= 200;
    }

    /**
     * Rule 2 – Description, when provided, must be at least 10 characters.
     */
    public function isDescriptionValid(Formation $formation): bool
    {
        $description = $formation->getDescription();
        if ($description === null) {
            return true; // nullable field — absence is valid
        }
        return strlen($description) >= 10;
    }

    /**
     * Rule 3 – dateFin must be strictly after dateDebut when both are set.
     */
    public function areDatesValid(Formation $formation): bool
    {
        $debut = $formation->getDateDebut();
        $fin   = $formation->getDateFin();

        if ($debut === null || $fin === null) {
            return true; // incomplete dates are allowed (partial fill)
        }

        return $fin > $debut;
    }

    /**
     * Rule 4 – A paid formation must have a price greater than zero.
     */
    public function isPrixCoherent(Formation $formation): bool
    {
        if (!$formation->isIsPaid()) {
            return true; // free formation — no price required
        }

        return $formation->getPrix() !== null && $formation->getPrix() > 0;
    }

    /**
     * Rule 5 – A formation cannot be marked "terminée" if it has no start date.
     */
    public function isTermineeCoherente(Formation $formation): bool
    {
        if ($formation->isTerminee() && $formation->getDateDebut() === null) {
            return false;
        }

        return true;
    }
}
