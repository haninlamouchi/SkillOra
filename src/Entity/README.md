# Entités Symfony - Plateforme Clubs

Ce dossier contient toutes les entités Symfony générées à partir de votre schéma de base de données MySQL.

## 📋 Liste des Entités

1. **User** - Gestion des utilisateurs (admin, responsable_club, etudiant)
2. **Club** - Gestion des clubs
3. **DemandeAdhesion** - Demandes d'adhésion aux clubs
4. **Publication** - Publications (image, video, texte)
5. **Commentaire** - Commentaires sur les publications
6. **Formation** - Formations proposées par les clubs
7. **Quiz** - Quiz liés aux formations
8. **Question** - Questions des quiz
9. **OptionQuestion** - Options/réponses des questions
10. **ResultatQuiz** - Résultats des quiz par utilisateur
11. **ParticipationFormation** - Participation des utilisateurs aux formations
12. **Challenge** - Défis proposés par les clubs
13. **Groupe** - Groupes d'utilisateurs
14. **MembreGroupe** - Membres des groupes
15. **Participation** - Participation des groupes aux challenges
16. **LivrableChallenge** - Livrables soumis par les groupes

## 🚀 Installation dans votre projet Symfony

### 1. Copier les entités

Copiez tous les fichiers `.php` dans le dossier `src/Entity/` de votre projet Symfony :

```bash
cp *.php /chemin/vers/votre/projet/src/Entity/
```

### 2. Créer les repositories

Pour chaque entité, vous devez créer un repository dans `src/Repository/`. Exemple pour `UserRepository.php` :

```php
<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
}
```

Répétez cette opération pour tous les repositories mentionnés dans les entités.

### 3. Configuration de Doctrine

Assurez-vous que votre fichier `config/packages/doctrine.yaml` est configuré correctement :

```yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        charset: utf8mb4
        default_table_options:
            charset: utf8mb4
            collate: utf8mb4_unicode_ci
    
    orm:
        auto_generate_proxy_classes: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        auto_mapping: true
        mappings:
            App:
                is_bundle: false
                type: attribute
                dir: '%kernel.project_dir%/src/Entity'
                prefix: 'App\Entity'
                alias: App
```

### 4. Configurer la base de données

Dans votre fichier `.env`, configurez votre connexion :

```env
DATABASE_URL="mysql://username:password@127.0.0.1:3306/plateforme_clubs?serverVersion=8.0"
```

### 5. Créer/Mettre à jour la base de données

Si vous partez d'une base vierge :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
```

Si la base existe déjà avec le schéma fourni, Doctrine devrait reconnaître les tables existantes.

## ⚠️ Points d'attention

### Champs ENUM

Les champs ENUM (comme `role` dans User et `typecontenu` dans Publication) utilisent une définition personnalisée :

```php
#[ORM\Column(type: Types::STRING, columnDefinition: "ENUM('admin', 'responsable_club', 'etudiant')")]
```

### Interface UserInterface

L'entité `User` implémente `UserInterface` et `PasswordAuthenticatedUserInterface` pour l'authentification Symfony. Vous devrez peut-être adapter les méthodes selon vos besoins :

- `getRoles()` : Retourne les rôles de l'utilisateur
- `getUserIdentifier()` : Retourne l'email comme identifiant
- `eraseCredentials()` : Efface les données sensibles temporaires

### Timestamps automatiques

Les champs avec `DEFAULT CURRENT_TIMESTAMP` sont initialisés dans les constructeurs :

```php
public function __construct()
{
    $this->dateInscription = new \DateTime();
}
```

## 🔧 Commandes utiles

### Valider le mapping

```bash
php bin/console doctrine:schema:validate
```

### Générer les migrations

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Mettre à jour le schéma

```bash
php bin/console doctrine:schema:update --force
```

## 📝 Personnalisation

N'hésitez pas à personnaliser ces entités selon vos besoins :

- Ajouter des méthodes métier
- Ajouter des validations avec les annotations Symfony Validator
- Ajouter des événements Doctrine (lifecycle callbacks)
- Personnaliser les getters/setters

## 🔗 Relations

Toutes les relations OneToMany, ManyToOne et les cascades sont déjà configurées selon votre schéma SQL :

- `CASCADE DELETE` : Configuré avec `onDelete: 'CASCADE'`
- `SET NULL` : Configuré avec `onDelete: 'SET NULL'`
- Collections : Initialisées dans les constructeurs avec `ArrayCollection`

## 📚 Documentation

Pour plus d'informations :
- [Documentation Doctrine](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/)
- [Documentation Symfony - Databases and Doctrine](https://symfony.com/doc/current/doctrine.html)
