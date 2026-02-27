<?php

namespace App\Entity;

use App\Repository\FormationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, Video>
     */
    #[ORM\OneToMany(
        targetEntity: Video::class,
        mappedBy: 'formation',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $videos;


    /**
     * @var Collection<int, ParticipationFormation>
     */
    #[ORM\OneToMany(
        targetEntity: ParticipationFormation::class,
        mappedBy: 'formation',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $participations;
    
    
    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(max: 200, maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(min: 10, minMessage: 'La description doit contenir au moins {{ limit }} caractères.')]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dateFin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lienRessources = null;

    #[ORM\ManyToOne(targetEntity: Club::class, inversedBy: 'formations')]
    #[ORM\JoinColumn(name: 'club_id', referencedColumnName: 'id_Club', nullable: true)]
    private ?Club $club = null;

    /**
     * @var Collection<int, Quiz>
     */
    #[ORM\OneToMany(
        targetEntity: Quiz::class,
        mappedBy: 'formation',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $quizzes;

    /**
     * @var Collection<int, Favorite>
     */
    #[ORM\OneToMany(targetEntity: Favorite::class, mappedBy: 'formation')]
    private Collection $favorites;
    
    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: Favorite::class)]
    private Collection $favoritesNum;

    public function __construct()
    {
        $this->quizzes = new ArrayCollection();
        $this->videos = new ArrayCollection();
        $this->participations = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->favoritesNum = new ArrayCollection();
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

    public function getDateDebut(): ?\DateTime
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTime $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTime $dateFin): static
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

    // --- Videos ---

    /**
     * @return Collection<int, Video>
     */
    public function getVideos(): Collection
    {
        return $this->videos;
    }

    public function addVideo(Video $video): static
    {
        if (!$this->videos->contains($video)) {
            $this->videos->add($video);
            $video->setFormation($this);
        }

        return $this;
    }

    public function removeVideo(Video $video): static
    {
        if ($this->videos->removeElement($video)) {
            if ($video->getFormation() === $this) {
                $video->setFormation(null);
            }
        }

        return $this;
    }

    // --- Participations ---

    /**
     * @return Collection<int, ParticipationFormation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(ParticipationFormation $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setFormation($this);
        }

        return $this;
    }

    public function removeParticipation(ParticipationFormation $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            if ($participation->getFormation() === $this) {
                $participation->setFormation(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->titre ?? '';
    }

    /**
     * @return Collection<int, Favorite>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Favorite $favorite): static
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
            $favorite->setFormation($this);
        }

        return $this;
    }

    public function removeFavorite(Favorite $favorite): static
    {
        if ($this->favorites->removeElement($favorite)) {
            // set the owning side to null (unless already changed)
            if ($favorite->getFormation() === $this) {
                $favorite->setFormation(null);
            }
        }

        return $this;
    }
    public function getFavoritesNum(): Collection
    {
        return $this->favoritesNum;
    }
}