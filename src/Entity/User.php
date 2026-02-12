<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'User')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_User')]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 150, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 50, columnDefinition: "ENUM('admin', 'responsable_club', 'etudiant')")]
    private ?string $role = null;


    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\OneToMany(targetEntity: Club::class, mappedBy: 'responsable')]
    private Collection $clubs;

    #[ORM\OneToMany(targetEntity: DemandeAdhesion::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $demandeAdhesions;

    #[ORM\OneToMany(targetEntity: Publication::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $publications;

    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $commentaires;

    #[ORM\OneToMany(targetEntity: ParticipationFormation::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $participationFormations;

    #[ORM\OneToMany(targetEntity: ResultatQuiz::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $resultatQuizzes;

    #[ORM\OneToMany(targetEntity: MembreGroupe::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $membreGroupes;

    public function __construct()
    {
        $this->dateInscription = new \DateTime();
        $this->clubs = new ArrayCollection();
        $this->demandeAdhesions = new ArrayCollection();
        $this->publications = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->participationFormations = new ArrayCollection();
        $this->resultatQuizzes = new ArrayCollection();
        $this->membreGroupes = new ArrayCollection();
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
    public function getDemandeAdhesions(): Collection
    {
        return $this->demandeAdhesions;
    }

    public function addDemandeAdhesion(DemandeAdhesion $demandeAdhesion): static
    {
        if (!$this->demandeAdhesions->contains($demandeAdhesion)) {
            $this->demandeAdhesions->add($demandeAdhesion);
            $demandeAdhesion->setUser($this);
        }
        return $this;
    }

    public function removeDemandeAdhesion(DemandeAdhesion $demandeAdhesion): static
    {
        if ($this->demandeAdhesions->removeElement($demandeAdhesion)) {
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
     * @return Collection<int, ParticipationFormation>
     */
    public function getParticipationFormations(): Collection
    {
        return $this->participationFormations;
    }

    public function addParticipationFormation(ParticipationFormation $participationFormation): static
    {
        if (!$this->participationFormations->contains($participationFormation)) {
            $this->participationFormations->add($participationFormation);
            $participationFormation->setUser($this);
        }
        return $this;
    }

    public function removeParticipationFormation(ParticipationFormation $participationFormation): static
    {
        if ($this->participationFormations->removeElement($participationFormation)) {
            if ($participationFormation->getUser() === $this) {
                $participationFormation->setUser(null);
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
            $resultatQuiz->setUser($this);
        }
        return $this;
    }

    public function removeResultatQuiz(ResultatQuiz $resultatQuiz): static
    {
        if ($this->resultatQuizzes->removeElement($resultatQuiz)) {
            if ($resultatQuiz->getUser() === $this) {
                $resultatQuiz->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, MembreGroupe>
     */
    public function getMembreGroupes(): Collection
    {
        return $this->membreGroupes;
    }

    public function addMembreGroupe(MembreGroupe $membreGroupe): static
    {
        if (!$this->membreGroupes->contains($membreGroupe)) {
            $this->membreGroupes->add($membreGroupe);
            $membreGroupe->setUser($this);
        }
        return $this;
    }

    public function removeMembreGroupe(MembreGroupe $membreGroupe): static
    {
        if ($this->membreGroupes->removeElement($membreGroupe)) {
            if ($membreGroupe->getUser() === $this) {
                $membreGroupe->setUser(null);
            }
        }
        return $this;
    }
}
