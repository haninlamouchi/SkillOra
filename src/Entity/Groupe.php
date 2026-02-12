<?php

namespace App\Entity;

use App\Repository\GroupeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupeRepository::class)]
#[ORM\Table(name: 'Groupe')]
class Groupe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_Groupe')]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $nomGroupe = null;

    #[ORM\OneToMany(targetEntity: MembreGroupe::class, mappedBy: 'groupe', orphanRemoval: true)]
    private Collection $membreGroupes;

    #[ORM\OneToMany(targetEntity: Participation::class, mappedBy: 'groupe', orphanRemoval: true)]
    private Collection $participations;

    #[ORM\OneToMany(targetEntity: LivrableChallenge::class, mappedBy: 'groupe', orphanRemoval: true)]
    private Collection $livrableChallenges;

    public function __construct()
    {
        $this->membreGroupes = new ArrayCollection();
        $this->participations = new ArrayCollection();
        $this->livrableChallenges = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, MembreGroupe>
     */
    public function getMembreGroupes(): Collection
    {
        return $this->membreGroupes;
    }

    public function addMembreGroupe(MembreGroupe $membreGroupe): static
    {
        if (!$this->membreGroupes->contains($membreGroupe)) {
            $this->membreGroupes->add($membreGroupe);
            $membreGroupe->setGroupe($this);
        }
        return $this;
    }

    public function removeMembreGroupe(MembreGroupe $membreGroupe): static
    {
        if ($this->membreGroupes->removeElement($membreGroupe)) {
            if ($membreGroupe->getGroupe() === $this) {
                $membreGroupe->setGroupe(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Participation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setGroupe($this);
        }
        return $this;
    }

    public function removeParticipation(Participation $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            if ($participation->getGroupe() === $this) {
                $participation->setGroupe(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, LivrableChallenge>
     */
    public function getLivrableChallenges(): Collection
    {
        return $this->livrableChallenges;
    }

    public function addLivrableChallenge(LivrableChallenge $livrableChallenge): static
    {
        if (!$this->livrableChallenges->contains($livrableChallenge)) {
            $this->livrableChallenges->add($livrableChallenge);
            $livrableChallenge->setGroupe($this);
        }
        return $this;
    }

    public function removeLivrableChallenge(LivrableChallenge $livrableChallenge): static
    {
        if ($this->livrableChallenges->removeElement($livrableChallenge)) {
            if ($livrableChallenge->getGroupe() === $this) {
                $livrableChallenge->setGroupe(null);
            }
        }
        return $this;
    }
}
