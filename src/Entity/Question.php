<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Quiz $quiz = null;

    /**
     * @var Collection<int, OptionQuestion>
     */
    #[ORM\OneToMany(
        targetEntity: OptionQuestion::class,
        mappedBy: 'question',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['ordre' => 'ASC', 'id' => 'ASC'])]
    private Collection $optionQuestions;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le contenu de la question est obligatoire.')]
    #[Assert\Length(min: 5, minMessage: 'Le contenu doit contenir au moins {{ limit }} caractères.')]
    private ?string $contenu = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: 'Les points doivent être un nombre positif.')]
    private ?int $points = null;

    #[ORM\Column(length:50)]
    private ?string $type = 'multiple_choice';

    public function __construct()
    {
        $this->optionQuestions = new ArrayCollection();
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
            // set the owning side to null (unless already changed)
            if ($optionQuestion->getQuestion() === $this) {
                $optionQuestion->setQuestion(null);
            }
        }

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

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(?int $points): static
    {
        $this->points = $points;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
