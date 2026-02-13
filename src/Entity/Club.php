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
    }

    public function getId(): ?int
    {
        return $this->id;
    }

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

    // Backward-compatible aliases (consider removing once you refactor callers)
    /** @deprecated Use getFormations() */
    public function getYes(): Collection
    {
        return $this->getFormations();
    }

    /** @deprecated Use addFormation() */
    public function addYe(Formation $ye): static
    {
        return $this->addFormation($ye);
    }

    /** @deprecated Use removeFormation() */
    public function removeYe(Formation $ye): static
    {
        return $this->removeFormation($ye);
    }

    public function __toString(): string
    {
        return 'Club #' . $this->id;
    }
}
