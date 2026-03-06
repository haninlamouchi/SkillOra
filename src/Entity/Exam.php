<?php

namespace App\Entity;

use App\Repository\ExamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExamRepository::class)]
class Exam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'exam', targetEntity: Formation::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Formation $formation = null;

    #[ORM\Column(length: 255)]
    private string $titre = 'Examen Final';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /** Minimum score (%) to pass */
    #[ORM\Column(type: 'integer')]
    private int $passingScore = 70;

    /** Maximum wrong answers before the exam stops */
    #[ORM\Column(type: 'integer')]
    private int $maxMistakes = 3;

    #[ORM\OneToMany(
        mappedBy: 'exam',
        targetEntity: ExamQuestion::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $questions;

    #[ORM\OneToMany(
        mappedBy: 'exam',
        targetEntity: ExamAttempt::class,
        cascade: ['remove'],
        orphanRemoval: true
    )]
    private Collection $attempts;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->attempts  = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getFormation(): ?Formation { return $this->formation; }
    public function setFormation(?Formation $formation): static { $this->formation = $formation; return $this; }

    public function getTitre(): string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getPassingScore(): int { return $this->passingScore; }
    public function setPassingScore(int $passingScore): static { $this->passingScore = $passingScore; return $this; }

    public function getMaxMistakes(): int { return $this->maxMistakes; }
    public function setMaxMistakes(int $maxMistakes): static { $this->maxMistakes = $maxMistakes; return $this; }

    /** @return Collection<int, ExamQuestion> */
    public function getQuestions(): Collection { return $this->questions; }

    public function addQuestion(ExamQuestion $q): static
    {
        if (!$this->questions->contains($q)) {
            $this->questions->add($q);
            $q->setExam($this);
        }
        return $this;
    }

    public function removeQuestion(ExamQuestion $q): static
    {
        if ($this->questions->removeElement($q)) {
            if ($q->getExam() === $this) $q->setExam(null);
        }
        return $this;
    }

    /** @return Collection<int, ExamAttempt> */
    public function getAttempts(): Collection { return $this->attempts; }
}
