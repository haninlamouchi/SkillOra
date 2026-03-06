<?php

namespace App\Entity;

use App\Repository\FlashcardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FlashcardRepository::class)]
class Flashcard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private ?string $question = null;

    #[ORM\Column(type: 'text')]
    private ?string $answer = null;

    #[ORM\ManyToOne(inversedBy: 'flashcards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Formation $formation = null;

    public function getId(): ?int { return $this->id; }

    public function getQuestion(): ?string { return $this->question; }
    public function setQuestion(string $question): static {
        $this->question = $question;
        return $this;
    }

    public function getAnswer(): ?string { return $this->answer; }
    public function setAnswer(string $answer): static {
        $this->answer = $answer;
        return $this;
    }

    public function getFormation(): ?Formation { return $this->formation; }
    public function setFormation(?Formation $formation): static {
        $this->formation = $formation;
        return $this;
    }
}