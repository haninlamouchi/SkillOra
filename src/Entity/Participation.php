<?php

namespace App\Entity;

use App\Repository\ParticipationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
#[ORM\Table(name: 'Participation')]
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_Participation')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Challenge::class, inversedBy: 'participations')]
    #[ORM\JoinColumn(name: 'id_Challenge', referencedColumnName: 'id_Challenge', nullable: false, onDelete: 'CASCADE')]
    private ?Challenge $challenge = null;

    #[ORM\ManyToOne(targetEntity: Groupe::class, inversedBy: 'participations')]
    #[ORM\JoinColumn(name: 'id_Groupe', referencedColumnName: 'id_Groupe', nullable: false, onDelete: 'CASCADE')]
    private ?Groupe $groupe = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $dateParticipation = null;

    public function __construct()
    {
        $this->dateParticipation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChallenge(): ?Challenge
    {
        return $this->challenge;
    }

    public function setChallenge(?Challenge $challenge): static
    {
        $this->challenge = $challenge;
        return $this;
    }

    public function getGroupe(): ?Groupe
    {
        return $this->groupe;
    }

    public function setGroupe(?Groupe $groupe): static
    {
        $this->groupe = $groupe;
        return $this;
    }

    public function getDateParticipation(): ?\DateTimeInterface
    {
        return $this->dateParticipation;
    }

    public function setDateParticipation(\DateTimeInterface $dateParticipation): static
    {
        $this->dateParticipation = $dateParticipation;
        return $this;
    }
}
