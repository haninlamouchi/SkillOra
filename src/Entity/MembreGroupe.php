<?php

namespace App\Entity;

use App\Repository\MembreGroupeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MembreGroupeRepository::class)]
#[ORM\Table(name: 'membre_groupe', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'user_groupe_unique', columns: ['user_id', 'groupe_id'])
])]
class MembreGroupe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $role = 'membre';

    #[ORM\ManyToOne(inversedBy: 'membresGroupes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;


    #[ORM\ManyToOne(inversedBy: 'membres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Groupe $groupe = null;

     public function __toString(): string
    {
        return $this->user ? $this->user->getEmail() : '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

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

    public function getGroupe(): ?Groupe
    {
        return $this->groupe;
    }

    public function setGroupe(?Groupe $groupe): static
    {
        $this->groupe = $groupe;

        return $this;
    }

}
