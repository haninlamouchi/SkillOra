<?php

namespace App\Enum;

enum TypeContenu: string
{
    case IMAGE = 'image';
    case VIDEO = 'video';
    case TEXTE = 'texte';

    /**
     * Retourne tous les types sous forme de tableau associatif pour les formulaires
     */
    public static function getChoices(): array
    {
        return [
            'Image' => self::IMAGE->value,
            'Vidéo' => self::VIDEO->value,
            'Texte' => self::TEXTE->value,
        ];
    }

    /**
     * Retourne le label lisible du type
     */
    public function getLabel(): string
    {
        return match($this) {
            self::IMAGE => 'Image',
            self::VIDEO => 'Vidéo',
            self::TEXTE => 'Texte',
        };
    }
}