<?php

namespace App\Entity;

use App\Repository\EvaluationMembreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EvaluationMembreRepository::class)]
class EvaluationMembre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'evaluationsMembres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?LivrableChallenge $livrable = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_User', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $noteIndividuelle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaireIndividuel = null;

    #[ORM\Column(nullable: true)]
    private ?int $contributionPourcentage = null;

    #[ORM\Column(nullable: true)]
    private ?int $commitsCount = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLivrable(): ?LivrableChallenge
    {
        return $this->livrable;
    }

    public function setLivrable(?LivrableChallenge $livrable): static
    {
        $this->livrable = $livrable;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getNoteIndividuelle(): ?string
    {
        return $this->noteIndividuelle;
    }

    public function setNoteIndividuelle(?string $noteIndividuelle): static
    {
        $this->noteIndividuelle = $noteIndividuelle;
        return $this;
    }

    public function getCommentaireIndividuel(): ?string
    {
        return $this->commentaireIndividuel;
    }

    public function setCommentaireIndividuel(?string $commentaireIndividuel): static
    {
        $this->commentaireIndividuel = $commentaireIndividuel;
        return $this;
    }

    public function getContributionPourcentage(): ?int
    {
        return $this->contributionPourcentage;
    }

    public function setContributionPourcentage(?int $contributionPourcentage): static
    {
        $this->contributionPourcentage = $contributionPourcentage;
        return $this;
    }

    public function getCommitsCount(): ?int
    {
        return $this->commitsCount;
    }

    public function setCommitsCount(?int $commitsCount): static
    {
        $this->commitsCount = $commitsCount;
        return $this;
    }
}