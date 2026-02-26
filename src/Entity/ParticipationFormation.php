<?php

namespace App\Entity;

use App\Repository\ParticipationFormationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipationFormationRepository::class)]
#[ORM\Table(name: 'ParticipationFormation')]
class ParticipationFormation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_Participation')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'participationFormations')]
    #[ORM\JoinColumn(name: 'id_User', referencedColumnName: 'id_User', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'participationFormations')]
    #[ORM\JoinColumn(name: 'id_Formation', referencedColumnName: 'id_Formation', nullable: false, onDelete: 'CASCADE')]
    private ?Formation $formation = null;

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

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): static
    {
        $this->formation = $formation;
        return $this;
    }
}
