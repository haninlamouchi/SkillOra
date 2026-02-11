<?php

namespace App\Entity;

use App\Repository\ChallengeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;


#[ORM\Entity(repositoryClass: ChallengeRepository::class)]
class Challenge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fichierCahierCharges = null;

    #[ORM\OneToMany(mappedBy: 'challenge', targetEntity: Participation::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $participations;

    #[ORM\OneToMany(mappedBy: 'challenge', targetEntity: LivrableChallenge::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $livrables;




    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function __toString(): string
    {
        return $this->titre ?? '';
    }



    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTime $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getFichierCahierCharges(): ?string
    {
        return $this->fichierCahierCharges;
    }

    public function setFichierCahierCharges(?string $fichierCahierCharges): static
    {
        $this->fichierCahierCharges = $fichierCahierCharges;

        return $this;
    }

    public function __construct()
    {
        $this->participations = new ArrayCollection();
        $this->livrables = new ArrayCollection();
    }

    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): static
    {    
    if (!$this->participations->contains($participation)) {
        $this->participations->add($participation);
        $participation->setChallenge($this);
    }

        return $this;
    }

    public function removeParticipation(Participation $participation): static
    {
    if ($this->participations->removeElement($participation)) {
        if ($participation->getChallenge() === $this) {
            $participation->setChallenge(null);
        }
    }

        return $this;
    }

    public function getLivrables(): Collection
    {
        return $this->livrables;
    }


}
