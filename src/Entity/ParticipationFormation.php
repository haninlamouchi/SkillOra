<?php

namespace App\Entity;

use App\Repository\ParticipationFormationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipationFormationRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_participation', columns: ['user_id', 'formation_id'])]
class ParticipationFormation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Formation $formation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $dateParticipation = null;

    public function __construct()
    {
        $this->dateParticipation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): static
    {
        $this->formation = $formation;

        return $this;
    }

    public function getDateParticipation(): ?\DateTime
    {
        return $this->dateParticipation;
    }

    public function setDateParticipation(\DateTime $dateParticipation): static
    {
        $this->dateParticipation = $dateParticipation;

        return $this;
    }
}
