<?php
// src/Event/DemandeMembreEvent.php
namespace App\Event;

use App\Entity\DemandeMembre;
use Symfony\Contracts\EventDispatcher\Event;

class DemandeMembreEvent extends Event
{
    public const NAME = 'demande.membre.created';

    public function __construct(private DemandeMembre $demande) {}

    public function getDemande(): DemandeMembre
    {
        return $this->demande;
    }
}