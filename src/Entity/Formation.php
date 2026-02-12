<?php

namespace App\Entity;

use App\Repository\FormationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
#[ORM\Table(name: 'Formation')]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_Formation')]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lienRessources = null;

    #[ORM\ManyToOne(targetEntity: Club::class, inversedBy: 'formations')]
    #[ORM\JoinColumn(name: 'id_Club', referencedColumnName: 'id_Club', nullable: false, onDelete: 'CASCADE')]
    private ?Club $club = null;

    #[ORM\OneToMany(targetEntity: Quiz::class, mappedBy: 'formation', orphanRemoval: true)]
    private Collection $quizzes;

    #[ORM\OneToMany(targetEntity: ParticipationFormation::class, mappedBy: 'formation', orphanRemoval: true)]
    private Collection $participationFormations;

    public function __construct()
    {
        $this->quizzes = new ArrayCollection();
        $this->participationFormations = new ArrayCollection();
    }

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

    public function setDateDebut(?\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
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

    public function getLienRessources(): ?string
    {
        return $this->lienRessources;
    }

    public function setLienRessources(?string $lienRessources): static
    {
        $this->lienRessources = $lienRessources;
        return $this;
    }

    public function getClub(): ?Club
    {
        return $this->club;
    }

    public function setClub(?Club $club): static
    {
        $this->club = $club;
        return $this;
    }

    /**
     * @return Collection<int, Quiz>
     */
    public function getQuizzes(): Collection
    {
        return $this->quizzes;
    }

    public function addQuiz(Quiz $quiz): static
    {
        if (!$this->quizzes->contains($quiz)) {
            $this->quizzes->add($quiz);
            $quiz->setFormation($this);
        }
        return $this;
    }

    public function removeQuiz(Quiz $quiz): static
    {
        if ($this->quizzes->removeElement($quiz)) {
            if ($quiz->getFormation() === $this) {
                $quiz->setFormation(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, ParticipationFormation>
     */
    public function getParticipationFormations(): Collection
    {
        return $this->participationFormations;
    }

    public function addParticipationFormation(ParticipationFormation $participationFormation): static
    {
        if (!$this->participationFormations->contains($participationFormation)) {
            $this->participationFormations->add($participationFormation);
            $participationFormation->setFormation($this);
        }
        return $this;
    }

    public function removeParticipationFormation(ParticipationFormation $participationFormation): static
    {
        if ($this->participationFormations->removeElement($participationFormation)) {
            if ($participationFormation->getFormation() === $this) {
                $participationFormation->setFormation(null);
            }
        }
        return $this;
    }
}
