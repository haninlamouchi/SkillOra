<?php

namespace App\Entity;

use App\Repository\ClubRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ClubRepository::class)]
#[Vich\Uploadable] 
class Club
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_Club')]
    private ?int $id_Club = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[Vich\UploadableField(mapping: 'club_logo', fileNameProperty: 'logo')]
    #[Assert\File(
        maxSize: '2M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/gif'],
        mimeTypesMessage: 'Veuillez uploader une image valide (jpeg, png, gif).',
        maxSizeMessage: 'Le fichier ne doit pas dépasser 2Mo.'
    )]
    private ?File $logoFile = null;

    #[ORM\Column(nullable: true)] 
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\LessThanOrEqual(value: 'today')]
    private ?\DateTime $dateCreation = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: 'Veuillez entrer une URL valide.')]
    private ?string $siteWeb = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'clubs')]
    #[ORM\JoinColumn(name: 'responsable_id', referencedColumnName: 'id_User', nullable: true)]
    private ?User $responsable = null;
    
    /**
     * @var Collection<int, Formation>
     */
    #[ORM\OneToMany(mappedBy: 'club', targetEntity: Formation::class)]
    private Collection $formations;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'clubs')]
    #[ORM\JoinTable(name: 'club_membre',
        joinColumns: [
            new ORM\JoinColumn(name: 'id_Club', referencedColumnName: 'id_Club')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'id_User', referencedColumnName: 'id_User')
        ]
    )]
    private Collection $membres;

    public function __construct()
    {
        $this->membres = new ArrayCollection();
        $this->formations = new ArrayCollection();
    }

    // --- Getters / Setters ---

    public function getId(): ?int
    {
        return $this->id_Club;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    public function setLogoFile(?File $logoFile = null): void
    {
        $this->logoFile = $logoFile;

        if ($logoFile !== null) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getLogoFile(): ?File
    {
        return $this->logoFile;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?\DateTime $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getSiteWeb(): ?string
    {
        return $this->siteWeb;
    }

    public function setSiteWeb(?string $siteWeb): static
    {
        $this->siteWeb = $siteWeb;
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
        }

        return $this;
    }

    public function removeMembre(User $user): static
    {
        $this->membres->removeElement($user);
        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}