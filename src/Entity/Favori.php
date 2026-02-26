<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'favori')]
#[ORM\UniqueConstraint(name: 'user_challenge_unique', columns: ['user_id', 'challenge_id'])]
class Favori
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_User', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Challenge::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Challenge $challenge = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateAjout = null;

    public function __construct()
    {
        $this->dateAjout = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getChallenge(): ?Challenge { return $this->challenge; }
    public function setChallenge(?Challenge $challenge): static { $this->challenge = $challenge; return $this; }

    public function getDateAjout(): ?\DateTimeImmutable { return $this->dateAjout; }
}