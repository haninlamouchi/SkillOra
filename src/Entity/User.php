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

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'User')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_User')]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ\s]+$/u',
        message: 'Le nom ne peut contenir que des lettres et des espaces!'
    )]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ\s]+$/u',
        message: 'Le prénom ne peut contenir que des lettres et des espaces!'
    )]
    private ?string $prenom = null;

    #[ORM\Column(length: 150, unique: true)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'L\'email {{ value }} n\'est pas un email valide!')]
    #[Assert\Length(max: 150)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]  // ← CETTE LIGNE EST IMPORTANTE
    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire')]
    #[Assert\Length(
    min: 8,
    minMessage:'Le mot de passe doit contenir au moins {{ limit }} caractères!'
    )]
    #[Assert\Regex(
    pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/',
    message: 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial!.'
    )]
private ?string $password = null;
    

    #[ORM\Column(type: Types::STRING, columnDefinition: "ENUM('admin', 'responsable_club', 'etudiant')")]
    #[Assert\NotBlank(message: 'Le rôle est obligatoire')]
    #[Assert\Choice(
        choices: ['admin', 'responsable_club', 'etudiant'],
        message: 'Le rôle sélectionné n\'est pas valide'
    )]
    private ?string $role = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\NotBlank(message: 'Le téléphone est obligatoire')]
    #[Assert\Regex(
        pattern: '/^[\d\s\+\-\(\)]+$/',
        message: 'Le numéro de téléphone n\'est pas valide'
    )]
    #[Assert\Length(
        min: 8,
        max: 20,
        minMessage: 'Le téléphone doit contenir au moins {{ limit }} chiffres',
        maxMessage: 'Le téléphone ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $telephone = null;

#[ORM\Column(length: 255, nullable: true)]
private ?string $photo = null;


    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\NotBlank(message: 'La date de naissance est obligatoire')]
    #[Assert\LessThan(
        value: 'today',
        message: 'La date de naissance doit être antérieure à aujourd\'hui'
    )]
    private ?\DateTimeInterface $dateNaissance = null;


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
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
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
    public function getRoles(): array
{
    $roles = [];
    
    switch ($this->role) {
        case 'admin':
            $roles[] = 'ROLE_ADMIN';
            break;
        case 'responsable_club':
            $roles[] = 'ROLE_RESPONSABLE_CLUB';
            break;
        case 'etudiant':
            $roles[] = 'ROLE_ETUDIANT';
            break;
    }
    
    $roles[] = 'ROLE_USER';
    
    return array_unique($roles);  // Retourne ['ROLE_ETUDIANT', 'ROLE_USER']
}

    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTimeInterface $dateInscription): static
    {
        $this->dateInscription = $dateInscription;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    /**
     * @return Collection<int, Club>
     */
    public function getClubs(): Collection
    {
        return $this->clubs;
    }

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

    /**
     * @return Collection<int, DemandeAdhesion>
     */
    public function getDemandesAdhesion(): Collection
    {
        return $this->demandesAdhesion;
    }

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

    /**
     * @return Collection<int, Publication>
     */
    public function getPublications(): Collection
    {
        return $this->publications;
    }

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

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

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

    /**
     * @return Collection<int, ResultatQuiz>
     */
    public function getResultatsQuiz(): Collection
    {
        return $this->resultatsQuiz;
    }

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

    /**
     * @return Collection<int, ParticipationFormation>
     */
    public function getParticipationsFormation(): Collection
    {
        return $this->participationsFormation;
    }

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

    /**
     * @return Collection<int, MembreGroupe>
     */
    public function getMembresGroupe(): Collection
    {
        return $this->membresGroupe;
    }

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

    public function eraseCredentials(): void
    {
        // Si vous stockez des données sensibles temporaires, effacez-les ici
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }
}
