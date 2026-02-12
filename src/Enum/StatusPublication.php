<?php

namespace App\Enum;

enum StatusPublication: string
{
    case EN_ATTENTE = 'en attente';
    case PUBLIE = 'publié';

    /**
     * Retourne tous les statuts sous forme de tableau associatif pour les formulaires
     */
    public static function getChoices(): array
    {
        return [
            'En attente' => self::EN_ATTENTE->value,
            'Publié' => self::PUBLIE->value,
        ];
    }

    /**
     * Retourne le label lisible du statut
     */
    public function getLabel(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::PUBLIE => 'Publié',
        };
    }

    /**
     * Retourne le statut par défaut
     */
    public static function default(): self
    {
        return self::EN_ATTENTE;
    }
}