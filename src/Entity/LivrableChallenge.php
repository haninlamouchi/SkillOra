<?php

namespace App\Entity;

use App\Repository\LivrableChallengeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;


#[ORM\Entity(repositoryClass: LivrableChallengeRepository::class)]
#[ORM\Table(name: 'livrable_challenge', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'groupe_challenge_unique', columns: ['groupe_id', 'challenge_id'])
])]
class LivrableChallenge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $fichier = null;
    
    #[ORM\Column(length: 20)]
    private ?string $statut = 'en_attente';

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $githubUrl = null;


    #[ORM\Column]
    private ?\DateTimeImmutable $dateSoumission = null;

    #[ORM\ManyToOne(inversedBy: 'livrables')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Groupe $groupe = null;


    #[ORM\ManyToOne(inversedBy: 'livrables')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Challenge $challenge = null;
    public function __toString(): string
{
    return $this->fichier ?? '';
}
     #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $noteCode = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $noteFonctionnalites = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $noteDesign = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $noteDocumentation = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $noteGlobale = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $pointsForts = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $pointsAmeliorer = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $suggestions = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEvaluation = null;

    #[ORM\OneToMany(mappedBy: 'livrable', targetEntity: EvaluationMembre::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $evaluationsMembres;

    public function __construct()
    {
        $this->evaluationsMembres = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFichier(): ?string
    {
        return $this->fichier;
    }

    public function setFichier(string $fichier): static
    {
        $this->fichier = $fichier;

        return $this;
    }

    public function getDateSoumission(): ?\DateTimeImmutable
    {
        return $this->dateSoumission;
    }

    public function setDateSoumission(\DateTimeImmutable $dateSoumission): static
    {
        $this->dateSoumission = $dateSoumission;

        return $this;
    }

    public function getChallenge(): ?Challenge
    {
        return $this->challenge;
    }

    public function setChallenge(?Challenge $challenge): static
    {
        $this->challenge = $challenge;

        return $this;
    }

    public function getGroupe(): ?Groupe
    {
        return $this->groupe;
    }

    public function setGroupe(?Groupe $groupe): static
    {
        $this->groupe = $groupe;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getGithubUrl(): ?string
    {
        return $this->githubUrl;
    }

    public function setGithubUrl(?string $githubUrl): self
    {
        $this->githubUrl = $githubUrl;
        return $this;
    }

    public function getNoteCode(): ?string
    {
        return $this->noteCode;
    }

    public function setNoteCode(?string $noteCode): static
    {
        $this->noteCode = $noteCode;
        return $this;
    }

    public function getNoteFonctionnalites(): ?string
    {
        return $this->noteFonctionnalites;
    }

    public function setNoteFonctionnalites(?string $noteFonctionnalites): static
    {
        $this->noteFonctionnalites = $noteFonctionnalites;
        return $this;
    }

    public function getNoteDesign(): ?string
    {
        return $this->noteDesign;
    }

    public function setNoteDesign(?string $noteDesign): static
    {
        $this->noteDesign = $noteDesign;
        return $this;
    }

    public function getNoteDocumentation(): ?string
    {
        return $this->noteDocumentation;
    }

    public function setNoteDocumentation(?string $noteDocumentation): static
    {
        $this->noteDocumentation = $noteDocumentation;
        return $this;
    }

    public function getNoteGlobale(): ?string
    {
        return $this->noteGlobale;
    }

    public function setNoteGlobale(?string $noteGlobale): static
    {
        $this->noteGlobale = $noteGlobale;
        return $this;
    }

    public function getPointsForts(): ?string
    {
        return $this->pointsForts;
    }

    public function setPointsForts(?string $pointsForts): static
    {
        $this->pointsForts = $pointsForts;
        return $this;
    }

    public function getPointsAmeliorer(): ?string
    {
        return $this->pointsAmeliorer;
    }

    public function setPointsAmeliorer(?string $pointsAmeliorer): static
    {
        $this->pointsAmeliorer = $pointsAmeliorer;
        return $this;
    }

    public function getSuggestions(): ?string
    {
        return $this->suggestions;
    }

    public function setSuggestions(?string $suggestions): static
    {
        $this->suggestions = $suggestions;
        return $this;
    }

    public function getDateEvaluation(): ?\DateTimeInterface
    {
        return $this->dateEvaluation;
    }

    public function setDateEvaluation(?\DateTimeInterface $dateEvaluation): static
    {
        $this->dateEvaluation = $dateEvaluation;
        return $this;
    }

    public function getEvaluationsMembres(): Collection
    {
        return $this->evaluationsMembres;
    }

    public function addEvaluationMembre(EvaluationMembre $evaluationMembre): static
    {
        if (!$this->evaluationsMembres->contains($evaluationMembre)) {
            $this->evaluationsMembres->add($evaluationMembre);
            $evaluationMembre->setLivrable($this);
        }
        return $this;
    }

    public function removeEvaluationMembre(EvaluationMembre $evaluationMembre): static
    {
        if ($this->evaluationsMembres->removeElement($evaluationMembre)) {
            if ($evaluationMembre->getLivrable() === $this) {
                $evaluationMembre->setLivrable(null);
            }
        }
        return $this;
    }

}
