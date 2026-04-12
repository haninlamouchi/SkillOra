<?php

namespace App\Entity;

use App\Repository\DemandeClubRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: DemandeClubRepository::class)]
#[Vich\Uploadable]
class DemandeClub
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

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

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(referencedColumnName: 'id_User')]
    private ?User $responsable = null;

    // ── NOUVEAUX CHAMPS (nullable: true = safe pour la DB) ──

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cv = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: 'Veuillez entrer une URL LinkedIn valide.')]
    private ?string $linkedin = null;

    // ── GETTERS / SETTERS EXISTANTS ──

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getLogo(): ?string { return $this->logo; }
    public function setLogo(?string $logo): static { $this->logo = $logo; return $this; }

    public function setLogoFile(?File $logoFile = null): void
    {
        $this->logoFile = $logoFile;
        if ($logoFile !== null) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }
    public function getLogoFile(): ?File { return $this->logoFile; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function getDateCreation(): ?\DateTime { return $this->dateCreation; }
    public function setDateCreation(?\DateTime $dateCreation): static { $this->dateCreation = $dateCreation; return $this; }

    public function getSiteWeb(): ?string { return $this->siteWeb; }
    public function setSiteWeb(?string $siteWeb): static { $this->siteWeb = $siteWeb; return $this; }

    public function getResponsable(): ?User { return $this->responsable; }
    public function setResponsable(?User $responsable): static { $this->responsable = $responsable; return $this; }

    // ── NOUVEAUX GETTERS / SETTERS ──

    public function getCv(): ?string { return $this->cv; }
    public function setCv(?string $cv): static { $this->cv = $cv; return $this; }

    public function getLinkedin(): ?string { return $this->linkedin; }
    public function setLinkedin(?string $linkedin): static { $this->linkedin = $linkedin; return $this; }
}