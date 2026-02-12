<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209205750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Challenge (id_Challenge INT AUTO_INCREMENT NOT NULL, titre VARCHAR(200) NOT NULL, description LONGTEXT DEFAULT NULL, date_debut DATE DEFAULT NULL, date_fin DATE DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, fichier_cahier_charges VARCHAR(255) DEFAULT NULL, id_Club INT NOT NULL, INDEX IDX_55F80BF293FC8B4E (id_Club), PRIMARY KEY (id_Challenge)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE Club (id_Club INT AUTO_INCREMENT NOT NULL, nom VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, date_creation DATE DEFAULT NULL, email VARCHAR(150) DEFAULT NULL, site_web VARCHAR(255) DEFAULT NULL, responsableId INT DEFAULT NULL, INDEX IDX_18DC974C1B4DFBDA (responsableId), PRIMARY KEY (id_Club)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE Commentaire (id_Commentaire INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_commentaire DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, id_User INT NOT NULL, id_Publication INT NOT NULL, INDEX IDX_E16CE76BA6816575 (id_User), INDEX IDX_E16CE76B31B22559 (id_Publication), PRIMARY KEY (id_Commentaire)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE DemandeAdhesion (id_DemandeAdhesion INT AUTO_INCREMENT NOT NULL, date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, id_User INT NOT NULL, id_Club INT NOT NULL, INDEX IDX_F000A040A6816575 (id_User), INDEX IDX_F000A04093FC8B4E (id_Club), PRIMARY KEY (id_DemandeAdhesion)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE Formation (id_Formation INT AUTO_INCREMENT NOT NULL, titre VARCHAR(200) NOT NULL, description LONGTEXT DEFAULT NULL, date_debut DATE DEFAULT NULL, date_fin DATE DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, lien_ressources VARCHAR(255) DEFAULT NULL, id_Club INT NOT NULL, INDEX IDX_C2B1A31C93FC8B4E (id_Club), PRIMARY KEY (id_Formation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE Groupe (id_Groupe INT AUTO_INCREMENT NOT NULL, nom_groupe VARCHAR(150) NOT NULL, PRIMARY KEY (id_Groupe)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE LivrableChallenge (id_LivrableChallenge INT AUTO_INCREMENT NOT NULL, fichier VARCHAR(255) DEFAULT NULL, date_soumission DATETIME DEFAULT NULL, id_Groupe INT NOT NULL, INDEX IDX_3D1232C25223CFA (id_Groupe), PRIMARY KEY (id_LivrableChallenge)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE MembreGroupe (id_Membregroupe INT AUTO_INCREMENT NOT NULL, role VARCHAR(50) DEFAULT NULL, id_Groupe INT NOT NULL, id_User INT NOT NULL, INDEX IDX_273AB1F225223CFA (id_Groupe), INDEX IDX_273AB1F2A6816575 (id_User), PRIMARY KEY (id_Membregroupe)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE OptionQuestion (id_OptionQuestion INT AUTO_INCREMENT NOT NULL, contenu VARCHAR(255) NOT NULL, est_correct TINYINT DEFAULT 0 NOT NULL, ordre INT DEFAULT NULL, id_Question INT NOT NULL, INDEX IDX_EE59A5C81F5AC78D (id_Question), PRIMARY KEY (id_OptionQuestion)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE Participation (id_Participation INT AUTO_INCREMENT NOT NULL, date_participation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, id_Challenge INT NOT NULL, id_Groupe INT NOT NULL, INDEX IDX_182BA9BAD5CDB7D5 (id_Challenge), INDEX IDX_182BA9BA25223CFA (id_Groupe), PRIMARY KEY (id_Participation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE ParticipationFormation (id_Participation INT AUTO_INCREMENT NOT NULL, id_User INT NOT NULL, id_Formation INT NOT NULL, INDEX IDX_88C6F828A6816575 (id_User), INDEX IDX_88C6F82842841F3B (id_Formation), PRIMARY KEY (id_Participation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE Publication (id_Publication INT AUTO_INCREMENT NOT NULL, titre VARCHAR(200) NOT NULL, contenu LONGTEXT NOT NULL, typecontenu ENUM(\'image\', \'video\', \'texte\'), date_publication DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, status VARCHAR(50) DEFAULT NULL, id_User INT NOT NULL, INDEX IDX_29A0E8AEA6816575 (id_User), PRIMARY KEY (id_Publication)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE Question (id_Question INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, points INT DEFAULT NULL, id_Quiz INT NOT NULL, INDEX IDX_4F812B188F0049AE (id_Quiz), PRIMARY KEY (id_Question)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE Quiz (id_Quiz INT AUTO_INCREMENT NOT NULL, titre VARCHAR(200) NOT NULL, description LONGTEXT DEFAULT NULL, duree INT DEFAULT NULL, nb_questions INT DEFAULT NULL, id_Formation INT NOT NULL, INDEX IDX_42055AC42841F3B (id_Formation), PRIMARY KEY (id_Quiz)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE ResultatQuiz (id_ResultatQuiz INT AUTO_INCREMENT NOT NULL, score INT DEFAULT NULL, scoremin INT DEFAULT NULL, id_Quiz INT NOT NULL, id_User INT NOT NULL, INDEX IDX_58A1D10B8F0049AE (id_Quiz), INDEX IDX_58A1D10BA6816575 (id_User), PRIMARY KEY (id_ResultatQuiz)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE User (id_User INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, email VARCHAR(150) NOT NULL, password VARCHAR(255) NOT NULL, role ENUM(\'admin\', \'responsable_club\', \'etudiant\'), date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, telephone VARCHAR(20) DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, date_naissance DATE DEFAULT NULL, UNIQUE INDEX UNIQ_2DA17977E7927C74 (email), PRIMARY KEY (id_User)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE Challenge ADD CONSTRAINT FK_55F80BF293FC8B4E FOREIGN KEY (id_Club) REFERENCES Club (id_Club) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Club ADD CONSTRAINT FK_18DC974C1B4DFBDA FOREIGN KEY (responsableId) REFERENCES User (id_User) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE Commentaire ADD CONSTRAINT FK_E16CE76BA6816575 FOREIGN KEY (id_User) REFERENCES User (id_User) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Commentaire ADD CONSTRAINT FK_E16CE76B31B22559 FOREIGN KEY (id_Publication) REFERENCES Publication (id_Publication) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE DemandeAdhesion ADD CONSTRAINT FK_F000A040A6816575 FOREIGN KEY (id_User) REFERENCES User (id_User) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE DemandeAdhesion ADD CONSTRAINT FK_F000A04093FC8B4E FOREIGN KEY (id_Club) REFERENCES Club (id_Club) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Formation ADD CONSTRAINT FK_C2B1A31C93FC8B4E FOREIGN KEY (id_Club) REFERENCES Club (id_Club) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE LivrableChallenge ADD CONSTRAINT FK_3D1232C25223CFA FOREIGN KEY (id_Groupe) REFERENCES Groupe (id_Groupe) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE MembreGroupe ADD CONSTRAINT FK_273AB1F225223CFA FOREIGN KEY (id_Groupe) REFERENCES Groupe (id_Groupe) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE MembreGroupe ADD CONSTRAINT FK_273AB1F2A6816575 FOREIGN KEY (id_User) REFERENCES User (id_User) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE OptionQuestion ADD CONSTRAINT FK_EE59A5C81F5AC78D FOREIGN KEY (id_Question) REFERENCES Question (id_Question) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Participation ADD CONSTRAINT FK_182BA9BAD5CDB7D5 FOREIGN KEY (id_Challenge) REFERENCES Challenge (id_Challenge) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Participation ADD CONSTRAINT FK_182BA9BA25223CFA FOREIGN KEY (id_Groupe) REFERENCES Groupe (id_Groupe) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ParticipationFormation ADD CONSTRAINT FK_88C6F828A6816575 FOREIGN KEY (id_User) REFERENCES User (id_User) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ParticipationFormation ADD CONSTRAINT FK_88C6F82842841F3B FOREIGN KEY (id_Formation) REFERENCES Formation (id_Formation) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Publication ADD CONSTRAINT FK_29A0E8AEA6816575 FOREIGN KEY (id_User) REFERENCES User (id_User) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Question ADD CONSTRAINT FK_4F812B188F0049AE FOREIGN KEY (id_Quiz) REFERENCES Quiz (id_Quiz) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Quiz ADD CONSTRAINT FK_42055AC42841F3B FOREIGN KEY (id_Formation) REFERENCES Formation (id_Formation) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ResultatQuiz ADD CONSTRAINT FK_58A1D10B8F0049AE FOREIGN KEY (id_Quiz) REFERENCES Quiz (id_Quiz) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ResultatQuiz ADD CONSTRAINT FK_58A1D10BA6816575 FOREIGN KEY (id_User) REFERENCES User (id_User) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Challenge DROP FOREIGN KEY FK_55F80BF293FC8B4E');
        $this->addSql('ALTER TABLE Club DROP FOREIGN KEY FK_18DC974C1B4DFBDA');
        $this->addSql('ALTER TABLE Commentaire DROP FOREIGN KEY FK_E16CE76BA6816575');
        $this->addSql('ALTER TABLE Commentaire DROP FOREIGN KEY FK_E16CE76B31B22559');
        $this->addSql('ALTER TABLE DemandeAdhesion DROP FOREIGN KEY FK_F000A040A6816575');
        $this->addSql('ALTER TABLE DemandeAdhesion DROP FOREIGN KEY FK_F000A04093FC8B4E');
        $this->addSql('ALTER TABLE Formation DROP FOREIGN KEY FK_C2B1A31C93FC8B4E');
        $this->addSql('ALTER TABLE LivrableChallenge DROP FOREIGN KEY FK_3D1232C25223CFA');
        $this->addSql('ALTER TABLE MembreGroupe DROP FOREIGN KEY FK_273AB1F225223CFA');
        $this->addSql('ALTER TABLE MembreGroupe DROP FOREIGN KEY FK_273AB1F2A6816575');
        $this->addSql('ALTER TABLE OptionQuestion DROP FOREIGN KEY FK_EE59A5C81F5AC78D');
        $this->addSql('ALTER TABLE Participation DROP FOREIGN KEY FK_182BA9BAD5CDB7D5');
        $this->addSql('ALTER TABLE Participation DROP FOREIGN KEY FK_182BA9BA25223CFA');
        $this->addSql('ALTER TABLE ParticipationFormation DROP FOREIGN KEY FK_88C6F828A6816575');
        $this->addSql('ALTER TABLE ParticipationFormation DROP FOREIGN KEY FK_88C6F82842841F3B');
        $this->addSql('ALTER TABLE Publication DROP FOREIGN KEY FK_29A0E8AEA6816575');
        $this->addSql('ALTER TABLE Question DROP FOREIGN KEY FK_4F812B188F0049AE');
        $this->addSql('ALTER TABLE Quiz DROP FOREIGN KEY FK_42055AC42841F3B');
        $this->addSql('ALTER TABLE ResultatQuiz DROP FOREIGN KEY FK_58A1D10B8F0049AE');
        $this->addSql('ALTER TABLE ResultatQuiz DROP FOREIGN KEY FK_58A1D10BA6816575');
        $this->addSql('DROP TABLE Challenge');
        $this->addSql('DROP TABLE Club');
        $this->addSql('DROP TABLE Commentaire');
        $this->addSql('DROP TABLE DemandeAdhesion');
        $this->addSql('DROP TABLE Formation');
        $this->addSql('DROP TABLE Groupe');
        $this->addSql('DROP TABLE LivrableChallenge');
        $this->addSql('DROP TABLE MembreGroupe');
        $this->addSql('DROP TABLE OptionQuestion');
        $this->addSql('DROP TABLE Participation');
        $this->addSql('DROP TABLE ParticipationFormation');
        $this->addSql('DROP TABLE Publication');
        $this->addSql('DROP TABLE Question');
        $this->addSql('DROP TABLE Quiz');
        $this->addSql('DROP TABLE ResultatQuiz');
        $this->addSql('DROP TABLE User');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
