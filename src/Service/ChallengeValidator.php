<?php

namespace App\Service;

use App\Entity\Challenge;

class ChallengeValidator
{
    private const NIVEAUX_VALIDES = ['Débutant', 'Intermédiaire', 'Avancé'];
    
    /**
     * Vérifie que le titre n'est pas vide
     */
    public function validateTitre(Challenge $challenge): bool
    {
        if (empty($challenge->getTitre())) {
            throw new \InvalidArgumentException('Le titre du challenge est obligatoire');
        }
        
        return true;
    }
    
    /**
     * Vérifie que la date de fin est après la date de début
     */
    public function validateDates(Challenge $challenge): bool
    {
        $dateDebut = $challenge->getDateDebut();
        $dateFin = $challenge->getDateFin();
        
        if ($dateDebut === null || $dateFin === null) {
            throw new \InvalidArgumentException('Les dates de début et de fin sont obligatoires');
        }
        
        if ($dateFin <= $dateDebut) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début');
        }
        
        return true;
    }
    
    /**
     * Vérifie que la description n'est pas vide et contient au moins 10 caractères
     */
    public function validateDescription(Challenge $challenge): bool
    {
        $description = $challenge->getDescription();
        
        if (empty($description)) {
            throw new \InvalidArgumentException('La description du challenge est obligatoire');
        }
        
        if (strlen($description) < 10) {
            throw new \InvalidArgumentException('La description doit contenir au moins 10 caractères');
        }
        
        return true;
    }
    
    /**
     * Validation complète du challenge
     */
    public function validate(Challenge $challenge): bool
    {
        $this->validateTitre($challenge);
        $this->validateDescription($challenge);
        $this->validateDates($challenge);
        
        return true;
    }
}