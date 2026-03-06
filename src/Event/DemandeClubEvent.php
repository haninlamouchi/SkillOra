<?php

namespace App\Event;

use App\Entity\DemandeClub;
use Symfony\Contracts\EventDispatcher\Event;

class DemandeClubEvent extends Event
{
    public const NAME = 'demande.club.created';

    public function __construct(private DemandeClub $demande) {}

    public function getDemande(): DemandeClub
    {
        return $this->demande;
    }
}