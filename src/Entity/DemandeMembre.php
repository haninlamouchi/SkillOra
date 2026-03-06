<?php

namespace App\Entity;

use App\Repository\DemandeMembreRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\StatutDemande;

#[ORM\Entity(repositoryClass: DemandeMembreRepository::class)]
class DemandeMembre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'id_Club')]
    private ?Club $club = null;

    #[ORM\ManyToOne]  
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'id_User')]
    private ?User $user = null;
    

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $dateInscription = null;

    #[ORM\Column(enumType: StatutDemande::class)]
    private ?StatutDemande $statut = null;

    public function getId(): ?int { return $this->id; }

    public function getClub(): ?Club { return $this->club; }
    public function setClub(?Club $club): static { $this->club = $club; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getDateInscription(): ?\DateTime { return $this->dateInscription; }
    public function setDateInscription(\DateTime $dateInscription): static { $this->dateInscription = $dateInscription; return $this; }

    public function getStatut(): ?StatutDemande { return $this->statut; }
    public function setStatut(StatutDemande $statut): static { $this->statut = $statut; return $this; }
}