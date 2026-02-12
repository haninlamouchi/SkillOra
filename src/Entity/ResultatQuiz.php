<?php

namespace App\Entity;

use App\Repository\ResultatQuizRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResultatQuizRepository::class)]
#[ORM\Table(name: 'ResultatQuiz')]
class ResultatQuiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_ResultatQuiz')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Quiz::class, inversedBy: 'resultats')]
    #[ORM\JoinColumn(name: 'id_Quiz', referencedColumnName: 'id_Quiz', nullable: false, onDelete: 'CASCADE')]
    private ?Quiz $quiz = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'resultatsQuiz')]
    #[ORM\JoinColumn(name: 'id_User', referencedColumnName: 'id_User', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?int $score = null;

    #[ORM\Column(nullable: true)]
    private ?int $scoremin = null;

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

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): static
    {
        $this->score = $score;
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
}
