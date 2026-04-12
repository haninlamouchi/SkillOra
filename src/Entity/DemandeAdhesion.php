<?php

namespace App\Entity;

use App\Repository\DemandeAdhesionRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\StatutDemande;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: DemandeAdhesionRepository::class)]
#[Vich\Uploadable]  // 👈 ajouté
class DemandeAdhesion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'demandesAdhesion')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_User', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'id_Club')]
    private ?Club $club = null;

    #[ORM\Column]
    private ?\DateTime $dateInscription = null;

    #[ORM\Column(type: 'string', enumType: StatutDemande::class)]
    private StatutDemande $statut = StatutDemande::en_attente;

    // ── NOUVEAUX CHAMPS (nullable: true = safe pour la DB) ──

    #[ORM\Column(length: 255, nullable: true)]  // nom du fichier stocké
    private ?string $cv = null;

    #[Vich\UploadableField(mapping: 'cv_upload', fileNameProperty: 'cv')]
    #[Assert\File(
        maxSize: '5M',
        mimeTypes: ['application/pdf'],
        mimeTypesMessage: 'Veuillez uploader un fichier PDF uniquement.',
        maxSizeMessage: 'Le CV ne doit pas dépasser 5Mo.'
    )]
    private ?File $cvFile = null;  // pas en base, juste pour l'upload

    #[ORM\Column(length: 255, nullable: true)]  // URL LinkedIn
    #[Assert\Url(message: 'Veuillez entrer une URL LinkedIn valide.')]
    private ?string $linkedin = null;

    #[ORM\Column(nullable: true)]  // requis par VichUploader
    private ?\DateTimeImmutable $updatedAt = null;

    // ── GETTERS / SETTERS EXISTANTS ──

    public function getId(): ?int { return $this->id; }

    public function getStatut(): StatutDemande { return $this->statut; }
    public function setStatut(StatutDemande $statut): self { $this->statut = $statut; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getClub(): ?Club { return $this->club; }
    public function setClub(?Club $club): static { $this->club = $club; return $this; }

    public function getDateInscription(): ?\DateTime { return $this->dateInscription; }
    public function setDateInscription(\DateTime $dateInscription): static { $this->dateInscription = $dateInscription; return $this; }

    // ── NOUVEAUX GETTERS / SETTERS ──

    public function getCv(): ?string { return $this->cv; }
    public function setCv(?string $cv): static { $this->cv = $cv; return $this; }

    public function getCvFile(): ?File { return $this->cvFile; }
    public function setCvFile(?File $cvFile = null): void
    {
        $this->cvFile = $cvFile;
        if ($cvFile !== null) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getLinkedin(): ?string { return $this->linkedin; }
    public function setLinkedin(?string $linkedin): static { $this->linkedin = $linkedin; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
}