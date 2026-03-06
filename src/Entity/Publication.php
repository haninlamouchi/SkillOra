<?php

namespace App\Entity;

use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;
use App\Enum\TypeContenu;
use App\Enum\StatusPublication;
use App\Repository\PublicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PublicationRepository::class)]
#[ORM\Table(name: 'Publication')]
#[Vich\Uploadable]
class Publication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_Publication')]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: 'Le titre ne peut pas être vide.')]
    #[Assert\Length(
        min: 3,
        max: 200,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ0-9\s\-\'\",\.!?:;()]+$/u',
        message: 'Le titre contient des caractères invalides.'
    )]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le contenu ne peut pas être vide.')]
    #[Assert\Length(
        min: 10,
        max: 5000,
        minMessage: 'Le contenu doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le contenu ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $contenu = null;

    #[ORM\Column(type: 'string', length: 50, enumType: TypeContenu::class)]
    #[Assert\NotNull(message: 'Veuillez sélectionner un type de contenu.')]
    private ?TypeContenu $typecontenu = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $datePublication = null;

    #[ORM\Column(type: 'string', length: 50, enumType: StatusPublication::class)]
    private ?StatusPublication $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fichier = null;

    #[Vich\UploadableField(mapping: 'publication_fichier', fileNameProperty: 'fichier')]
    private ?File $imageFile = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'publications')]
    #[ORM\JoinColumn(name: 'id_User', referencedColumnName: 'id_User', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Un utilisateur est requis.')]
    private ?User $user = null;


    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'publication', cascade: ['remove'])]
    private Collection $commentaires;

    public function __construct()
    {
        $this->datePublication = new \DateTime();
        $this->status          = StatusPublication::default();
        $this->commentaires    = new ArrayCollection();
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;
        if ($imageFile) {
            $this->datePublication = new \DateTime();
        }
    }

    public function getImageFile(): ?File { return $this->imageFile; }

    public function getId(): ?int { return $this->id; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getContenu(): ?string { return $this->contenu; }
    public function setContenu(string $contenu): static { $this->contenu = $contenu; return $this; }

    public function getTypecontenu(): ?TypeContenu { return $this->typecontenu; }
    public function setTypecontenu(TypeContenu $typecontenu): static { $this->typecontenu = $typecontenu; return $this; }

    public function getDatePublication(): ?\DateTimeInterface { return $this->datePublication; }
    public function setDatePublication(\DateTimeInterface $datePublication): static { $this->datePublication = $datePublication; return $this; }

    public function getStatus(): ?StatusPublication { return $this->status; }
    public function setStatus(StatusPublication $status): static { $this->status = $status; return $this; }

    public function getFichier(): ?string { return $this->fichier; }
    public function setFichier(?string $fichier): static { $this->fichier = $fichier; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getCommentaires(): Collection { return $this->commentaires; }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setPublication($this);
        }
        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            if ($commentaire->getPublication() === $this) {
                $commentaire->setPublication(null);
            }
        }
        return $this;
    }
}