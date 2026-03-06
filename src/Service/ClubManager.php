<?php

namespace App\Service;

use App\Entity\Club;

class ClubManager
{
    public function validate(Club $club): bool
    {
        if (empty($club->getNom())) {
            throw new \InvalidArgumentException("Le club doit avoir un nom");
        }

        if ($club->getResponsable() === null) {
            throw new \InvalidArgumentException("Le club doit avoir un responsable");
        }

        return true;
    }
}