<?php

namespace App\Entity;

use App\Repository\DemandeAdhesionRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\StatutDemande;

#[ORM\Entity(repositoryClass: DemandeAdhesionRepository::class)]
class DemandeAdhesion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'demandesAdhesion')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_User', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'id_Club')]
    private ?Club $club = null;

    #[ORM\Column]
    private ?\DateTime $dateInscription = null;

    #[ORM\Column(type: 'string', enumType: StatutDemande::class)]
    private StatutDemande $statut = StatutDemande::en_attente;

    public function getStatut(): StatutDemande { return $this->statut; }
    public function setStatut(StatutDemande $statut): self { $this->statut = $statut; return $this; }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getClub(): ?Club { return $this->club; }
    public function setClub(?Club $club): static { $this->club = $club; return $this; }

    public function getDateInscription(): ?\DateTime { return $this->dateInscription; }
    public function setDateInscription(\DateTime $dateInscription): static { $this->dateInscription = $dateInscription; return $this; }
}