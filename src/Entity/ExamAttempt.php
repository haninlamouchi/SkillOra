<?php

namespace App\Entity;

use App\Repository\ExamAttemptRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Statuses:
 *  - 'in_progress' : started but not finished
 *  - 'passed'      : completed with score >= passingScore
 *  - 'failed'      : completed with score < passingScore
 *  - 'stopped'     : stopped early due to too many mistakes
 */
#[ORM\Entity(repositoryClass: ExamAttemptRepository::class)]
class ExamAttempt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'attempts', targetEntity: Exam::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Exam $exam = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_User', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /** JSON-encoded array of question IDs in randomized order */
    #[ORM\Column(type: 'text')]
    private string $questionOrder = '[]';

    /** JSON-encoded map: questionId => given answer (choice id or text) */
    #[ORM\Column(type: 'text')]
    private string $answers = '[]';

    #[ORM\Column(type: 'integer')]
    private int $score = 0;

    #[ORM\Column(type: 'integer')]
    private int $totalPoints = 0;

    #[ORM\Column(type: 'integer')]
    private int $mistakeCount = 0;

    #[ORM\Column(length: 20)]
    private string $status = 'in_progress';

    #[ORM\Column(type: 'datetime')]
    private \DateTime $startedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $completedAt = null;

    public function __construct()
    {
        $this->startedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getExam(): ?Exam { return $this->exam; }
    public function setExam(?Exam $exam): static { $this->exam = $exam; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getQuestionOrder(): string { return $this->questionOrder; }
    public function setQuestionOrder(string $questionOrder): static { $this->questionOrder = $questionOrder; return $this; }

    public function getAnswers(): string { return $this->answers; }
    public function setAnswers(string $answers): static { $this->answers = $answers; return $this; }

    public function getScore(): int { return $this->score; }
    public function setScore(int $score): static { $this->score = $score; return $this; }

    public function getTotalPoints(): int { return $this->totalPoints; }
    public function setTotalPoints(int $totalPoints): static { $this->totalPoints = $totalPoints; return $this; }

    public function getMistakeCount(): int { return $this->mistakeCount; }
    public function setMistakeCount(int $mistakeCount): static { $this->mistakeCount = $mistakeCount; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getStartedAt(): \DateTime { return $this->startedAt; }
    public function setStartedAt(\DateTime $startedAt): static { $this->startedAt = $startedAt; return $this; }

    public function getCompletedAt(): ?\DateTime { return $this->completedAt; }
    public function setCompletedAt(?\DateTime $completedAt): static { $this->completedAt = $completedAt; return $this; }

    /** Percentage score (0-100) */
    public function getPercentage(): int
    {
        if ($this->totalPoints === 0) return 0;
        return (int) round(($this->score / $this->totalPoints) * 100);
    }

    public function isPassed(): bool { return $this->status === 'passed'; }
}
