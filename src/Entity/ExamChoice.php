<?php

namespace App\Entity;

use App\Repository\ExamChoiceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExamChoiceRepository::class)]
class ExamChoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'choices', targetEntity: ExamQuestion::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ExamQuestion $question = null;

    #[ORM\Column(type: 'text')]
    private string $choiceText = '';

    #[ORM\Column(type: 'boolean')]
    private bool $isCorrect = false;

    public function getId(): ?int { return $this->id; }

    public function getQuestion(): ?ExamQuestion { return $this->question; }
    public function setQuestion(?ExamQuestion $question): static { $this->question = $question; return $this; }

    public function getChoiceText(): string { return $this->choiceText; }
    public function setChoiceText(string $choiceText): static { $this->choiceText = $choiceText; return $this; }

    public function isCorrect(): bool { return $this->isCorrect; }
    public function setIsCorrect(bool $isCorrect): static { $this->isCorrect = $isCorrect; return $this; }
}
