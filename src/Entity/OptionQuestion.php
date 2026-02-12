<?php

namespace App\Entity;

use App\Repository\OptionQuestionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OptionQuestionRepository::class)]
#[ORM\Table(name: 'OptionQuestion')]
class OptionQuestion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_OptionQuestion')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $contenu = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $estCorrect = false;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $ordre = null;

    #[ORM\ManyToOne(targetEntity: Question::class, inversedBy: 'optionQuestions')]
    #[ORM\JoinColumn(name: 'id_Question', referencedColumnName: 'id_Question', nullable: false, onDelete: 'CASCADE')]
    private ?Question $question = null;

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

    public function isEstCorrect(): ?bool
    {
        return $this->estCorrect;
    }

    public function setEstCorrect(bool $estCorrect): static
    {
        $this->estCorrect = $estCorrect;
        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(?int $ordre): static
    {
        $this->ordre = $ordre;
        return $this;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): static
    {
        $this->question = $question;
        return $this;
    }
}
