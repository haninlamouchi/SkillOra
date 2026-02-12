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
    private Collection $membres;

    #[ORM\OneToMany(targetEntity: Participation::class, mappedBy: 'groupe', orphanRemoval: true)]
    private Collection $participations;

    #[ORM\OneToMany(targetEntity: LivrableChallenge::class, mappedBy: 'groupe', orphanRemoval: true)]
    private Collection $livrables;

    public function __construct()
    {
        $this->membres = new ArrayCollection();
        $this->participations = new ArrayCollection();
        $this->livrables = new ArrayCollection();
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
    public function getMembres(): Collection
    {
        return $this->membres;
    }

    public function addMembre(MembreGroupe $membre): static
    {
        if (!$this->membres->contains($membre)) {
            $this->membres->add($membre);
            $membre->setGroupe($this);
        }
        return $this;
    }

    public function removeMembre(MembreGroupe $membre): static
    {
        if ($this->membres->removeElement($membre)) {
            if ($membre->getGroupe() === $this) {
                $membre->setGroupe(null);
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
    public function getLivrables(): Collection
    {
        return $this->livrables;
    }

    public function addLivrable(LivrableChallenge $livrable): static
    {
        if (!$this->livrables->contains($livrable)) {
            $this->livrables->add($livrable);
            $livrable->setGroupe($this);
        }
        return $this;
    }

    public function removeLivrable(LivrableChallenge $livrable): static
    {
        if ($this->livrables->removeElement($livrable)) {
            if ($livrable->getGroupe() === $this) {
                $livrable->setGroupe(null);
            }
        }
        return $this;
    }
}
