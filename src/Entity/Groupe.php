<?php

namespace App\Entity;

use App\Repository\GroupeRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;



#[ORM\Entity(repositoryClass: GroupeRepository::class)]
#[UniqueEntity(
    fields: ['nomGroupe'],
    message: "Ce nom de groupe existe déjà."
)]
class Groupe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom du groupe est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le nom ne doit pas dépasser {{ limit }} caractères."
    )]

    private ?string $nomGroupe = null;

    #[ORM\OneToMany(mappedBy: 'groupe', targetEntity: Participation::class)]
    private Collection $participations;

    #[ORM\OneToMany(mappedBy: 'groupe', targetEntity: MembreGroupe::class)]
    private Collection $membres;

    #[ORM\OneToMany(mappedBy: 'groupe', targetEntity: LivrableChallenge::class)]
    private Collection $livrables;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomGroupe(): ?string
    {
        return $this->nomGroupe;
    }

    public function setNomGroupe(string $nomGroupe): static
    {
        $this->nomGroupe = $nomGroupe;

        return $this;
    }

    public function __toString(): string
    {
        return $this->nomGroupe ?? '';
    }



    public function __construct()
    {
        $this->participations = new ArrayCollection();
        $this->membres = new ArrayCollection();
        $this->livrables = new ArrayCollection();
    }

    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function getMembres(): Collection
    {
        return $this->membres;
    }

    public function getLivrables(): Collection
    {
        return $this->livrables;
    }
    

}
