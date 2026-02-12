<?php

namespace App\Entity;

use App\Repository\OptionQuestionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OptionQuestionRepository::class)]
class OptionQuestion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'optionQuestions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Question $question = null;

    #[ORM\Column(length: 200)]
    private ?string $contenu = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $estCorrect = false;

    #[ORM\Column(nullable: true)]
    private ?int $ordre = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function isEstCorrect(): bool
    {
        return $this->estCorrect;
    }

    public function setEstCorrect(bool $estCorrect): static
    {
        $this->estCorrect = $estCorrect;

        return $this;
    }

    // Preferred aliases (optional for callers)
    public function isCorrect(): bool
    {
        return $this->estCorrect;
    }

    public function setCorrect(bool $correct): static
    {
        $this->estCorrect = $correct;

        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(?int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }
}
