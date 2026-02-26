<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'User')]
#[UniqueEntity(fields: ['email'], message: 'This email is already used')]
#[Vich\Uploadable]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_User')]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(min: 2, max: 100)]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\s]+$/u', message: 'Last name can only contain letters and spaces')]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(min: 2, max: 100)]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\s]+$/u', message: 'First name can only contain letters and spaces')]
    private ?string $prenom = null;

    #[ORM\Column(length: 150, unique: true)]
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Please enter a valid email address')]
    #[Assert\Length(max: 150)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: Types::STRING, columnDefinition: "ENUM('admin', 'responsable_club', 'etudiant', 'membre')")]
    private ?string $role = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\NotBlank(message: 'Phone number is required')]
    #[Assert\Regex(pattern: '/^[\d\s\+\-\(\)]+$/', message: 'Please enter a valid phone number')]
    #[Assert\Length(min: 8, max: 20)]
    private ?string $telephone = null;

    #[Vich\UploadableField(mapping: 'user_photo', fileNameProperty: 'photo')]
    private ?File $photoFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: 'Date of birth is required')]
    #[Assert\LessThan(value: 'today', message: 'Date of birth must be in the past')]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    #[ORM\OneToMany(targetEntity: Club::class, mappedBy: 'responsable')]
    private Collection $clubs;

    #[ORM\OneToMany(targetEntity: DemandeAdhesion::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $demandesAdhesion;

    #[ORM\OneToMany(targetEntity: Publication::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $publications;

    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $commentaires;

    #[ORM\OneToMany(targetEntity: ResultatQuiz::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $resultatsQuiz;

    #[ORM\OneToMany(targetEntity: ParticipationFormation::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $participationsFormation;

    #[ORM\OneToMany(targetEntity: MembreGroupe::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $membresGroupe;

    public function __construct()
    {
        $this->clubs = new ArrayCollection();
        $this->demandesAdhesion = new ArrayCollection();
        $this->publications = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->resultatsQuiz = new ArrayCollection();
        $this->participationsFormation = new ArrayCollection();
        $this->membresGroupe = new ArrayCollection();
        $this->dateInscription = new \DateTime();
        $this->role = 'etudiant';
    }

    /**
     * Empêche la sérialisation de photoFile (objet File non sérialisable)
     * tout en conservant l'identifiant Doctrine pour EntityUserProvider.
     */
    public function __serialize(): array
    {
        return [
            'id'                  => $this->id,
            'email'               => $this->email,
            'password'            => $this->password,
            'nom'                 => $this->nom,
            'prenom'              => $this->prenom,
            'role'                => $this->role,
            'telephone'           => $this->telephone,
            'photo'               => $this->photo,
            'dateNaissance'       => $this->dateNaissance,
            'dateInscription'     => $this->dateInscription,
            'resetToken'          => $this->resetToken,
            'resetTokenExpiresAt' => $this->resetTokenExpiresAt,
            // photoFile exclu volontairement — non sérialisable
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id                  = $data['id'];
        $this->email               = $data['email'];
        $this->password            = $data['password'];
        $this->nom                 = $data['nom'];
        $this->prenom              = $data['prenom'];
        $this->role                = $data['role'];
        $this->telephone           = $data['telephone'] ?? null;
        $this->photo               = $data['photo'] ?? null;
        $this->dateNaissance       = $data['dateNaissance'] ?? null;
        $this->dateInscription     = $data['dateInscription'] ?? null;
        $this->resetToken          = $data['resetToken'] ?? null;
        $this->resetTokenExpiresAt = $data['resetTokenExpiresAt'] ?? null;
        $this->photoFile           = null;
        // Collections initialisées vides — rechargées par Doctrine si besoin
        $this->clubs                  = new \Doctrine\Common\Collections\ArrayCollection();
        $this->demandesAdhesion       = new \Doctrine\Common\Collections\ArrayCollection();
        $this->publications           = new \Doctrine\Common\Collections\ArrayCollection();
        $this->commentaires           = new \Doctrine\Common\Collections\ArrayCollection();
        $this->resultatsQuiz          = new \Doctrine\Common\Collections\ArrayCollection();
        $this->participationsFormation = new \Doctrine\Common\Collections\ArrayCollection();
        $this->membresGroupe          = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(string $role): static { $this->role = $role; return $this; }

    public function getRoles(): array
    {
        $roles = [];
        switch ($this->role) {
            case 'admin':            $roles[] = 'ROLE_ADMIN'; break;
            case 'responsable_club': $roles[] = 'ROLE_RESPONSABLE_CLUB'; break;
            case 'etudiant':         $roles[] = 'ROLE_ETUDIANT'; break;
            case 'membre':           $roles[] = 'ROLE_MEMBRE'; break;
        }
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function getDateInscription(): ?\DateTimeInterface { return $this->dateInscription; }
    public function setDateInscription(\DateTimeInterface $dateInscription): static { $this->dateInscription = $dateInscription; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }

    public function getPhoto(): ?string { return $this->photo; }
    public function setPhoto(?string $photo): static { $this->photo = $photo; return $this; }

    public function setPhotoFile(?File $photoFile = null): void
    {
        $this->photoFile = $photoFile;
        // Requis par Vich pour déclencher la mise à jour
        if ($photoFile !== null) {
            $this->dateInscription = new \DateTime();
        }
    }

    public function getPhotoFile(): ?File { return $this->photoFile; }

    public function getDateNaissance(): ?\DateTimeInterface { return $this->dateNaissance; }
    public function setDateNaissance(?\DateTimeInterface $dateNaissance): static { $this->dateNaissance = $dateNaissance; return $this; }

    public function getResetToken(): ?string { return $this->resetToken; }
    public function setResetToken(?string $resetToken): self { $this->resetToken = $resetToken; return $this; }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface { return $this->resetTokenExpiresAt; }
    public function setResetTokenExpiresAt(?\DateTimeInterface $date): self { $this->resetTokenExpiresAt = $date; return $this; }

    public function getClubs(): Collection { return $this->clubs; }

    public function addClub(Club $club): static
    {
        if (!$this->clubs->contains($club)) {
            $this->clubs->add($club);
            $club->setResponsable($this);
        }
        return $this;
    }

    public function removeClub(Club $club): static
    {
        if ($this->clubs->removeElement($club)) {
            if ($club->getResponsable() === $this) {
                $club->setResponsable(null);
            }
        }
        return $this;
    }

    public function getDemandesAdhesion(): Collection { return $this->demandesAdhesion; }

    public function addDemandeAdhesion(DemandeAdhesion $demandeAdhesion): static
    {
        if (!$this->demandesAdhesion->contains($demandeAdhesion)) {
            $this->demandesAdhesion->add($demandeAdhesion);
            $demandeAdhesion->setUser($this);
        }
        return $this;
    }

    public function removeDemandeAdhesion(DemandeAdhesion $demandeAdhesion): static
    {
        if ($this->demandesAdhesion->removeElement($demandeAdhesion)) {
            if ($demandeAdhesion->getUser() === $this) {
                $demandeAdhesion->setUser(null);
            }
        }
        return $this;
    }

    public function getPublications(): Collection { return $this->publications; }

    public function addPublication(Publication $publication): static
    {
        if (!$this->publications->contains($publication)) {
            $this->publications->add($publication);
            $publication->setUser($this);
        }
        return $this;
    }

    public function removePublication(Publication $publication): static
    {
        if ($this->publications->removeElement($publication)) {
            if ($publication->getUser() === $this) {
                $publication->setUser(null);
            }
        }
        return $this;
    }

    public function getCommentaires(): Collection { return $this->commentaires; }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setUser($this);
        }
        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            if ($commentaire->getUser() === $this) {
                $commentaire->setUser(null);
            }
        }
        return $this;
    }

    public function getResultatsQuiz(): Collection { return $this->resultatsQuiz; }

    public function addResultatQuiz(ResultatQuiz $resultatQuiz): static
    {
        if (!$this->resultatsQuiz->contains($resultatQuiz)) {
            $this->resultatsQuiz->add($resultatQuiz);
            $resultatQuiz->setUser($this);
        }
        return $this;
    }

    public function removeResultatQuiz(ResultatQuiz $resultatQuiz): static
    {
        if ($this->resultatsQuiz->removeElement($resultatQuiz)) {
            if ($resultatQuiz->getUser() === $this) {
                $resultatQuiz->setUser(null);
            }
        }
        return $this;
    }

    public function getParticipationsFormation(): Collection { return $this->participationsFormation; }

    public function addParticipationFormation(ParticipationFormation $participationFormation): static
    {
        if (!$this->participationsFormation->contains($participationFormation)) {
            $this->participationsFormation->add($participationFormation);
            $participationFormation->setUser($this);
        }
        return $this;
    }

    public function removeParticipationFormation(ParticipationFormation $participationFormation): static
    {
        if ($this->participationsFormation->removeElement($participationFormation)) {
            if ($participationFormation->getUser() === $this) {
                $participationFormation->setUser(null);
            }
        }
        return $this;
    }

    public function getMembresGroupe(): Collection { return $this->membresGroupe; }

    public function addMembreGroupe(MembreGroupe $membreGroupe): static
    {
        if (!$this->membresGroupe->contains($membreGroupe)) {
            $this->membresGroupe->add($membreGroupe);
            $membreGroupe->setUser($this);
        }
        return $this;
    }

    public function removeMembreGroupe(MembreGroupe $membreGroupe): static
    {
        if ($this->membresGroupe->removeElement($membreGroupe)) {
            if ($membreGroupe->getUser() === $this) {
                $membreGroupe->setUser(null);
            }
        }
        return $this;
    }

    public function eraseCredentials(): void {}

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }
}