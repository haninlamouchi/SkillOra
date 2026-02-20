<?php

namespace App\Entity;

use App\Repository\ClubRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClubRepository::class)]
class Club
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $nom = null;

    /**
     * The responsable_club user who manages this club.
     */
    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'clubResponsable')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $responsable = null;

    /**
     * Students who joined this club.
     *
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'clubs')]
    #[ORM\JoinTable(name: 'club_membre')]
    private Collection $membres;

    /**
     * @var Collection<int, Formation>
     */
    #[ORM\OneToMany(
        targetEntity: Formation::class,
        mappedBy: 'club',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $formations;

    public function __construct()
    {
        $this->formations = new ArrayCollection();
        $this->membres = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getResponsable(): ?User
    {
        return $this->responsable;
    }

    public function setResponsable(?User $responsable): static
    {
        $this->responsable = $responsable;
        return $this;
    }

    // ── Membres ──────────────────────────────────

    /**
     * @return Collection<int, User>
     */
    public function getMembres(): Collection
    {
        return $this->membres;
    }

    public function addMembre(User $user): static
    {
        if (!$this->membres->contains($user)) {
            $this->membres->add($user);
            $user->addClub($this);
        }
        return $this;
    }

    public function removeMembre(User $user): static
    {
        if ($this->membres->removeElement($user)) {
            $user->removeClub($this);
        }
        return $this;
    }

    public function hasMembre(User $user): bool
    {
        return $this->membres->contains($user);
    }

    // ── Formations ───────────────────────────────

    /**
     * @return Collection<int, Formation>
     */
    public function getFormations(): Collection
    {
        return $this->formations;
    }

    public function addFormation(Formation $formation): static
    {
        if (!$this->formations->contains($formation)) {
            $this->formations->add($formation);
            $formation->setClub($this);
        }

        return $this;
    }

    public function removeFormation(Formation $formation): static
    {
        if ($this->formations->removeElement($formation)) {
            if ($formation->getClub() === $this) {
                $formation->setClub(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? 'Club #' . $this->id;
    }
}
