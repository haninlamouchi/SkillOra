<?php

namespace App\Event;

use App\Entity\DemandeAdhesion;
use Symfony\Contracts\EventDispatcher\Event;

class DemandeResponsableEvent extends Event
{
    public const NAME = 'demande.responsable.created';

    public function __construct(private DemandeAdhesion $demande) {}

    public function getDemande(): DemandeAdhesion
    {
        return $this->demande;
    }
}