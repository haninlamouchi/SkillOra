<?php

namespace App\Entity;

use App\Repository\ExamQuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Types:
 *  - 'mcq'       : multiple-choice, one correct ExamChoice
 *  - 'fill'      : fill-in-the-blank, correctAnswer stored as text
 *  - 'multi_mcq' : multiple correct choices (checkboxes)
 *  - 'drag_drop' : drag items into correct order
 *  - 'word_pick' : sentence with ____, choose the right word from chips
 */
#[ORM\Entity(repositoryClass: ExamQuestionRepository::class)]
class ExamQuestion
{
    public const TYPE_MCQ       = 'mcq';
    public const TYPE_FILL      = 'fill';
    public const TYPE_MULTI_MCQ = 'multi_mcq';  // multiple correct choices
    public const TYPE_DRAG_DROP = 'drag_drop';  // drag to order/match
    public const TYPE_WORD_PICK = 'word_pick';  // click a chip to fill ____ in sentence

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'questions', targetEntity: Exam::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Exam $exam = null;

    #[ORM\Column(type: 'text')]
    private string $questionText = '';

    /** 'mcq' or 'fill' */
    #[ORM\Column(length: 10)]
    private string $type = self::TYPE_MCQ;

    /** For fill-in-the-blank: the expected answer (case-insensitive comparison) */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $correctAnswer = null;

    #[ORM\Column(type: 'integer')]
    private int $points = 1;

    #[ORM\OneToMany(
        mappedBy: 'question',
        targetEntity: ExamChoice::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $choices;

    public function __construct()
    {
        $this->choices = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getExam(): ?Exam { return $this->exam; }
    public function setExam(?Exam $exam): static { $this->exam = $exam; return $this; }

    public function getQuestionText(): string { return $this->questionText; }
    public function setQuestionText(string $questionText): static { $this->questionText = $questionText; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getCorrectAnswer(): ?string { return $this->correctAnswer; }
    public function setCorrectAnswer(?string $correctAnswer): static { $this->correctAnswer = $correctAnswer; return $this; }

    public function getPoints(): int { return $this->points; }
    public function setPoints(int $points): static { $this->points = $points; return $this; }

    /** @return Collection<int, ExamChoice> */
    public function getChoices(): Collection { return $this->choices; }

    public function addChoice(ExamChoice $choice): static
    {
        if (!$this->choices->contains($choice)) {
            $this->choices->add($choice);
            $choice->setQuestion($this);
        }
        return $this;
    }

    public function removeChoice(ExamChoice $choice): static
    {
        if ($this->choices->removeElement($choice)) {
            if ($choice->getQuestion() === $this) $choice->setQuestion(null);
        }
        return $this;
    }

    /** Return the correct ExamChoice for MCQ questions */
    public function getCorrectChoice(): ?ExamChoice
    {
        foreach ($this->choices as $c) {
            if ($c->isCorrect()) return $c;
        }
        return null;
    }
}
