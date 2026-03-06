<?php

namespace App\Entity;

use App\Repository\UserFlashcardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserFlashcardRepository::class)]
#[ORM\UniqueConstraint(name: "user_flashcard_unique", columns: ["user_id", "flashcard_id"])]
class UserFlashcard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_User', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Flashcard $flashcard = null;

    #[ORM\Column(type: 'boolean')]
    private bool $known = false;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getFlashcard(): ?Flashcard
    {
        return $this->flashcard;
    }

    public function setFlashcard(?Flashcard $flashcard): static
    {
        $this->flashcard = $flashcard;
        return $this;
    }

    public function isKnown(): bool
    {
        return $this->known;
    }

    public function setKnown(bool $known): static
    {
        $this->known = $known;
        return $this;
    }
}