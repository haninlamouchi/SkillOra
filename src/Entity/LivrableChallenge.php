<?php

namespace App\Entity;

use App\Repository\LivrableChallengeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LivrableChallengeRepository::class)]
#[ORM\Table(name: 'livrable_challenge', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'groupe_challenge_unique', columns: ['groupe_id', 'challenge_id'])
])]
class LivrableChallenge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $fichier = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateSoumission = null;

    #[ORM\ManyToOne(inversedBy: 'livrables')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Groupe $groupe = null;


    #[ORM\ManyToOne(inversedBy: 'livrables')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Challenge $challenge = null;
    public function __toString(): string
{
    return $this->fichier ?? '';
}


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFichier(): ?string
    {
        return $this->fichier;
    }

    public function setFichier(string $fichier): static
    {
        $this->fichier = $fichier;

        return $this;
    }

    public function getDateSoumission(): ?\DateTimeImmutable
    {
        return $this->dateSoumission;
    }

    public function setDateSoumission(\DateTimeImmutable $dateSoumission): static
    {
        $this->dateSoumission = $dateSoumission;

        return $this;
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
}
