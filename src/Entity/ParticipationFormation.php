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

    // ✅ FIX: referencedColumnName obligatoire car la PK de User s'appelle 'id_User' et non 'id'
    #[ORM\ManyToOne(inversedBy: 'participationsFormation')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_User', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Formation $formation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $dateParticipation = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $paymentStatus = false;

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

    public function isPaymentStatus(): bool { return $this->paymentStatus; }
    public function setPaymentStatus(bool $paymentStatus): static { $this->paymentStatus = $paymentStatus; return $this; }
}