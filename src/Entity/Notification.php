<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Repository\NotificationRepository;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private ?string $message = null;

    #[ORM\Column]
    private bool $isLu = false;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    // 'commentaire' = etudiant commente → responsable reçoit
    // 'reponse'     = responsable répond → etudiant reçoit
    #[ORM\Column(length: 50)]
    private ?string $type = null;

    // URL vers laquelle rediriger quand on clique sur la notification
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $lienRedirection = null;

    // Rôle de l'expéditeur pour affichage côté étudiant
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $roleExpediteur = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_destinataire', referencedColumnName: 'id_User', onDelete: 'CASCADE')]
    private ?User $destinataire = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_expediteur', referencedColumnName: 'id_User', onDelete: 'CASCADE')]
    private ?User $expediteur = null;

    #[ORM\ManyToOne(targetEntity: Publication::class)]
    #[ORM\JoinColumn(name: 'id_publication', referencedColumnName: 'id_Publication', nullable: true, onDelete: 'CASCADE')]
    private ?Publication $publication = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getMessage(): ?string { return $this->message; }
    public function setMessage(string $message): static { $this->message = $message; return $this; }

    public function isLu(): bool { return $this->isLu; }
    public function setIsLu(bool $isLu): static { $this->isLu = $isLu; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getLienRedirection(): ?string { return $this->lienRedirection; }
    public function setLienRedirection(?string $lien): static { $this->lienRedirection = $lien; return $this; }

    public function getRoleExpediteur(): ?string { return $this->roleExpediteur; }
    public function setRoleExpediteur(?string $role): static { $this->roleExpediteur = $role; return $this; }

    public function getDestinataire(): ?User { return $this->destinataire; }
    public function setDestinataire(?User $u): static { $this->destinataire = $u; return $this; }

    public function getExpediteur(): ?User { return $this->expediteur; }
    public function setExpediteur(?User $u): static { $this->expediteur = $u; return $this; }

    public function getPublication(): ?Publication { return $this->publication; }
    public function setPublication(?Publication $p): static { $this->publication = $p; return $this; }
}
