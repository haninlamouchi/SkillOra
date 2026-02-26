<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
#[ORM\Table(name: 'Question')]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_Question')]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenu = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $points = null;

    #[ORM\ManyToOne(targetEntity: Quiz::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(name: 'id_Quiz', referencedColumnName: 'id_Quiz', nullable: false, onDelete: 'CASCADE')]
    private ?Quiz $quiz = null;

    #[ORM\OneToMany(targetEntity: OptionQuestion::class, mappedBy: 'question', orphanRemoval: true)]
    private Collection $optionQuestions;

    public function __construct()
    {
        $this->optionQuestions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(?int $points): static
    {
        $this->points = $points;
        return $this;
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

    /**
     * @return Collection<int, OptionQuestion>
     */
    public function getOptionQuestions(): Collection
    {
        return $this->optionQuestions;
    }

    public function addOptionQuestion(OptionQuestion $optionQuestion): static
    {
        if (!$this->optionQuestions->contains($optionQuestion)) {
            $this->optionQuestions->add($optionQuestion);
            $optionQuestion->setQuestion($this);
        }
        return $this;
    }

    public function removeOptionQuestion(OptionQuestion $optionQuestion): static
    {
        if ($this->optionQuestions->removeElement($optionQuestion)) {
            if ($optionQuestion->getQuestion() === $this) {
                $optionQuestion->setQuestion(null);
            }
        }
        return $this;
    }
}
