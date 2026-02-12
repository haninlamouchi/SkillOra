<?php

namespace App\Entity;

use App\Repository\QuizRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizRepository::class)]
#[ORM\Table(name: 'Quiz')]
class Quiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_Quiz')]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $duree = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $nbQuestions = null;

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'quizzes')]
    #[ORM\JoinColumn(name: 'id_Formation', referencedColumnName: 'id_Formation', nullable: false, onDelete: 'CASCADE')]
    private ?Formation $formation = null;

    #[ORM\OneToMany(targetEntity: Question::class, mappedBy: 'quiz', orphanRemoval: true)]
    private Collection $questions;

    #[ORM\OneToMany(targetEntity: ResultatQuiz::class, mappedBy: 'quiz', orphanRemoval: true)]
    private Collection $resultatQuizzes;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->resultatQuizzes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(?int $duree): static
    {
        $this->duree = $duree;
        return $this;
    }

    public function getNbQuestions(): ?int
    {
        return $this->nbQuestions;
    }

    public function setNbQuestions(?int $nbQuestions): static
    {
        $this->nbQuestions = $nbQuestions;
        return $this;
    }

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): static
    {
        $this->formation = $formation;
        return $this;
    }

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): static
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setQuiz($this);
        }
        return $this;
    }

    public function removeQuestion(Question $question): static
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getQuiz() === $this) {
                $question->setQuiz(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, ResultatQuiz>
     */
    public function getResultatQuizzes(): Collection
    {
        return $this->resultatQuizzes;
    }

    public function addResultatQuiz(ResultatQuiz $resultatQuiz): static
    {
        if (!$this->resultatQuizzes->contains($resultatQuiz)) {
            $this->resultatQuizzes->add($resultatQuiz);
            $resultatQuiz->setQuiz($this);
        }
        return $this;
    }

    public function removeResultatQuiz(ResultatQuiz $resultatQuiz): static
    {
        if ($this->resultatQuizzes->removeElement($resultatQuiz)) {
            if ($resultatQuiz->getQuiz() === $this) {
                $resultatQuiz->setQuiz(null);
            }
        }
        return $this;
    }
}
