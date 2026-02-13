<?php

namespace App\Entity;

use App\Repository\ResultatQuizRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResultatQuizRepository::class)]
class ResultatQuiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Quiz $quiz = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private int $score = 0;

    #[ORM\Column]
    private int $totalPoints = 0;

    #[ORM\Column(nullable: true)]
    private ?int $scoremin = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $reponses = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $datePassage = null;

    public function __construct()
    {
        $this->datePassage = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): static
    {
        $this->quiz = $quiz;
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

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;
        return $this;
    }

    public function getTotalPoints(): int
    {
        return $this->totalPoints;
    }

    public function setTotalPoints(int $totalPoints): static
    {
        $this->totalPoints = $totalPoints;
        return $this;
    }

    public function getScoremin(): ?int
    {
        return $this->scoremin;
    }

    public function setScoremin(?int $scoremin): static
    {
        $this->scoremin = $scoremin;
        return $this;
    }

    public function getDatePassage(): ?\DateTime
    {
        return $this->datePassage;
    }

    public function setDatePassage(\DateTime $datePassage): static
    {
        $this->datePassage = $datePassage;
        return $this;
    }

    public function getPercentage(): float
    {
        if ($this->totalPoints === 0) {
            return 0;
        }
        return round(($this->score / $this->totalPoints) * 100, 1);
    }

    public function getReponses(): ?array
    {
        return $this->reponses;
    }

    public function setReponses(?array $reponses): static
    {
        $this->reponses = $reponses;
        return $this;
    }
}
