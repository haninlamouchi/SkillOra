<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224094348 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Formation (id_Formation INT AUTO_INCREMENT NOT NULL, titre VARCHAR(200) NOT NULL, description LONGTEXT DEFAULT NULL, date_debut DATE DEFAULT NULL, date_fin DATE DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, lien_ressources VARCHAR(255) DEFAULT NULL, id_Club INT NOT NULL, INDEX IDX_C2B1A31C93FC8B4E (id_Club), PRIMARY KEY (id_Formation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE OptionQuestion (id_OptionQuestion INT AUTO_INCREMENT NOT NULL, contenu VARCHAR(255) NOT NULL, est_correct TINYINT NOT NULL, ordre INT DEFAULT NULL, id_Question INT NOT NULL, INDEX IDX_EE59A5C81F5AC78D (id_Question), PRIMARY KEY (id_OptionQuestion)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE ParticipationFormation (id_Participation INT AUTO_INCREMENT NOT NULL, id_User INT NOT NULL, id_Formation INT NOT NULL, INDEX IDX_88C6F828A6816575 (id_User), INDEX IDX_88C6F82842841F3B (id_Formation), PRIMARY KEY (id_Participation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE Publication (id_Publication INT AUTO_INCREMENT NOT NULL, titre VARCHAR(200) NOT NULL, contenu LONGTEXT NOT NULL, typecontenu VARCHAR(50) NOT NULL, date_publication DATETIME NOT NULL, status VARCHAR(50) NOT NULL, fichier VARCHAR(255) DEFAULT NULL, id_User INT NOT NULL, INDEX IDX_29A0E8AEA6816575 (id_User), PRIMARY KEY (id_Publication)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE Question (id_Question INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, points INT DEFAULT NULL, id_Quiz INT NOT NULL, INDEX IDX_4F812B188F0049AE (id_Quiz), PRIMARY KEY (id_Question)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE Quiz (id_Quiz INT AUTO_INCREMENT NOT NULL, titre VARCHAR(200) NOT NULL, description LONGTEXT DEFAULT NULL, duree INT DEFAULT NULL, nb_questions INT DEFAULT NULL, id_Formation INT NOT NULL, INDEX IDX_42055AC42841F3B (id_Formation), PRIMARY KEY (id_Quiz)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE ResultatQuiz (id_ResultatQuiz INT AUTO_INCREMENT NOT NULL, score INT DEFAULT NULL, scoremin INT DEFAULT NULL, id_Quiz INT NOT NULL, id_User INT NOT NULL, INDEX IDX_58A1D10B8F0049AE (id_Quiz), INDEX IDX_58A1D10BA6816575 (id_User), PRIMARY KEY (id_ResultatQuiz)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE User (id_User INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, email VARCHAR(150) NOT NULL, password VARCHAR(255) NOT NULL, role ENUM(\'admin\', \'responsable_club\', \'etudiant\', \'membre\'), date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, telephone VARCHAR(20) DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, date_naissance DATE DEFAULT NULL, reset_token VARCHAR(100) DEFAULT NULL, reset_token_expires_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_2DA17977E7927C74 (email), PRIMARY KEY (id_User)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE challenge (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, date_debut DATETIME DEFAULT NULL, date_fin DATETIME DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, fichier_cahier_charges VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE club (id_Club INT AUTO_INCREMENT NOT NULL, nom VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, date_creation DATE DEFAULT NULL, site_web VARCHAR(255) DEFAULT NULL, responsable_id INT DEFAULT NULL, INDEX IDX_B8EE387253C59D72 (responsable_id), PRIMARY KEY (id_Club)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE commentaire (id_commentaire INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_commentaire DATETIME NOT NULL, id_User INT NOT NULL, id_Publication INT NOT NULL, INDEX IDX_67F068BCA6816575 (id_User), INDEX IDX_67F068BC31B22559 (id_Publication), PRIMARY KEY (id_commentaire)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE demande_adhesion (id INT AUTO_INCREMENT NOT NULL, date_inscription DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, user_id INT NOT NULL, club_id INT NOT NULL, INDEX IDX_8355EF98A76ED395 (user_id), INDEX IDX_8355EF9861190A32 (club_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE demande_club (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, date_creation DATE DEFAULT NULL, site_web VARCHAR(255) DEFAULT NULL, responsable_id INT DEFAULT NULL, INDEX IDX_DF4B4AA753C59D72 (responsable_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE demande_membre (id INT AUTO_INCREMENT NOT NULL, date_inscription DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, club_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_B4F0BDC661190A32 (club_id), INDEX IDX_B4F0BDC6A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE favori (id INT AUTO_INCREMENT NOT NULL, date_ajout DATETIME NOT NULL, user_id INT NOT NULL, challenge_id INT NOT NULL, INDEX IDX_EF85A2CCA76ED395 (user_id), INDEX IDX_EF85A2CC98A21AC6 (challenge_id), UNIQUE INDEX user_challenge_unique (user_id, challenge_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE groupe (id INT AUTO_INCREMENT NOT NULL, nom_groupe VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE livrable_challenge (id INT AUTO_INCREMENT NOT NULL, fichier VARCHAR(255) NOT NULL, statut VARCHAR(20) NOT NULL, github_url VARCHAR(500) DEFAULT NULL, date_soumission DATETIME NOT NULL, groupe_id INT NOT NULL, challenge_id INT NOT NULL, INDEX IDX_A6EE46877A45358C (groupe_id), INDEX IDX_A6EE468798A21AC6 (challenge_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE membre_groupe (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(50) NOT NULL, user_id INT NOT NULL, groupe_id INT NOT NULL, INDEX IDX_9EB01998A76ED395 (user_id), INDEX IDX_9EB019987A45358C (groupe_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, message LONGTEXT NOT NULL, is_lu TINYINT NOT NULL, created_at DATETIME NOT NULL, type VARCHAR(50) NOT NULL, lien_redirection VARCHAR(500) DEFAULT NULL, role_expediteur VARCHAR(50) DEFAULT NULL, id_destinataire INT DEFAULT NULL, id_expediteur INT DEFAULT NULL, id_publication INT DEFAULT NULL, INDEX IDX_BF5476CADD688AE0 (id_destinataire), INDEX IDX_BF5476CAE2E4F59 (id_expediteur), INDEX IDX_BF5476CAB72EAA8E (id_publication), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE participation (id INT AUTO_INCREMENT NOT NULL, date_participation DATETIME NOT NULL, challenge_id INT NOT NULL, groupe_id INT NOT NULL, INDEX IDX_AB55E24F98A21AC6 (challenge_id), INDEX IDX_AB55E24F7A45358C (groupe_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE Formation ADD CONSTRAINT FK_C2B1A31C93FC8B4E FOREIGN KEY (id_Club) REFERENCES club (id_Club) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE OptionQuestion ADD CONSTRAINT FK_EE59A5C81F5AC78D FOREIGN KEY (id_Question) REFERENCES Question (id_Question) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ParticipationFormation ADD CONSTRAINT FK_88C6F828A6816575 FOREIGN KEY (id_User) REFERENCES User (id_User) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ParticipationFormation ADD CONSTRAINT FK_88C6F82842841F3B FOREIGN KEY (id_Formation) REFERENCES Formation (id_Formation) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Publication ADD CONSTRAINT FK_29A0E8AEA6816575 FOREIGN KEY (id_User) REFERENCES User (id_User) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Question ADD CONSTRAINT FK_4F812B188F0049AE FOREIGN KEY (id_Quiz) REFERENCES Quiz (id_Quiz) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Quiz ADD CONSTRAINT FK_42055AC42841F3B FOREIGN KEY (id_Formation) REFERENCES Formation (id_Formation) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ResultatQuiz ADD CONSTRAINT FK_58A1D10B8F0049AE FOREIGN KEY (id_Quiz) REFERENCES Quiz (id_Quiz) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ResultatQuiz ADD CONSTRAINT FK_58A1D10BA6816575 FOREIGN KEY (id_User) REFERENCES User (id_User) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE club ADD CONSTRAINT FK_B8EE387253C59D72 FOREIGN KEY (responsable_id) REFERENCES User (id_User)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCA6816575 FOREIGN KEY (id_User) REFERENCES User (id_User) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC31B22559 FOREIGN KEY (id_Publication) REFERENCES Publication (id_Publication) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande_adhesion ADD CONSTRAINT FK_8355EF98A76ED395 FOREIGN KEY (user_id) REFERENCES User (id_User)');
        $this->addSql('ALTER TABLE demande_adhesion ADD CONSTRAINT FK_8355EF9861190A32 FOREIGN KEY (club_id) REFERENCES club (id_Club)');
        $this->addSql('ALTER TABLE demande_club ADD CONSTRAINT FK_DF4B4AA753C59D72 FOREIGN KEY (responsable_id) REFERENCES User (id_User)');
        $this->addSql('ALTER TABLE demande_membre ADD CONSTRAINT FK_B4F0BDC661190A32 FOREIGN KEY (club_id) REFERENCES club (id_Club)');
        $this->addSql('ALTER TABLE demande_membre ADD CONSTRAINT FK_B4F0BDC6A76ED395 FOREIGN KEY (user_id) REFERENCES User (id_User)');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CCA76ED395 FOREIGN KEY (user_id) REFERENCES User (id_User)');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CC98A21AC6 FOREIGN KEY (challenge_id) REFERENCES challenge (id)');
        $this->addSql('ALTER TABLE livrable_challenge ADD CONSTRAINT FK_A6EE46877A45358C FOREIGN KEY (groupe_id) REFERENCES groupe (id)');
        $this->addSql('ALTER TABLE livrable_challenge ADD CONSTRAINT FK_A6EE468798A21AC6 FOREIGN KEY (challenge_id) REFERENCES challenge (id)');
        $this->addSql('ALTER TABLE membre_groupe ADD CONSTRAINT FK_9EB01998A76ED395 FOREIGN KEY (user_id) REFERENCES User (id_User)');
        $this->addSql('ALTER TABLE membre_groupe ADD CONSTRAINT FK_9EB019987A45358C FOREIGN KEY (groupe_id) REFERENCES groupe (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CADD688AE0 FOREIGN KEY (id_destinataire) REFERENCES User (id_User) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAE2E4F59 FOREIGN KEY (id_expediteur) REFERENCES User (id_User) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAB72EAA8E FOREIGN KEY (id_publication) REFERENCES Publication (id_Publication) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24F98A21AC6 FOREIGN KEY (challenge_id) REFERENCES challenge (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24F7A45358C FOREIGN KEY (groupe_id) REFERENCES groupe (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Formation DROP FOREIGN KEY FK_C2B1A31C93FC8B4E');
        $this->addSql('ALTER TABLE OptionQuestion DROP FOREIGN KEY FK_EE59A5C81F5AC78D');
        $this->addSql('ALTER TABLE ParticipationFormation DROP FOREIGN KEY FK_88C6F828A6816575');
        $this->addSql('ALTER TABLE ParticipationFormation DROP FOREIGN KEY FK_88C6F82842841F3B');
        $this->addSql('ALTER TABLE Publication DROP FOREIGN KEY FK_29A0E8AEA6816575');
        $this->addSql('ALTER TABLE Question DROP FOREIGN KEY FK_4F812B188F0049AE');
        $this->addSql('ALTER TABLE Quiz DROP FOREIGN KEY FK_42055AC42841F3B');
        $this->addSql('ALTER TABLE ResultatQuiz DROP FOREIGN KEY FK_58A1D10B8F0049AE');
        $this->addSql('ALTER TABLE ResultatQuiz DROP FOREIGN KEY FK_58A1D10BA6816575');
        $this->addSql('ALTER TABLE club DROP FOREIGN KEY FK_B8EE387253C59D72');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCA6816575');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC31B22559');
        $this->addSql('ALTER TABLE demande_adhesion DROP FOREIGN KEY FK_8355EF98A76ED395');
        $this->addSql('ALTER TABLE demande_adhesion DROP FOREIGN KEY FK_8355EF9861190A32');
        $this->addSql('ALTER TABLE demande_club DROP FOREIGN KEY FK_DF4B4AA753C59D72');
        $this->addSql('ALTER TABLE demande_membre DROP FOREIGN KEY FK_B4F0BDC661190A32');
        $this->addSql('ALTER TABLE demande_membre DROP FOREIGN KEY FK_B4F0BDC6A76ED395');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CCA76ED395');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CC98A21AC6');
        $this->addSql('ALTER TABLE livrable_challenge DROP FOREIGN KEY FK_A6EE46877A45358C');
        $this->addSql('ALTER TABLE livrable_challenge DROP FOREIGN KEY FK_A6EE468798A21AC6');
        $this->addSql('ALTER TABLE membre_groupe DROP FOREIGN KEY FK_9EB01998A76ED395');
        $this->addSql('ALTER TABLE membre_groupe DROP FOREIGN KEY FK_9EB019987A45358C');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CADD688AE0');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAE2E4F59');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAB72EAA8E');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24F98A21AC6');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24F7A45358C');
        $this->addSql('DROP TABLE Formation');
        $this->addSql('DROP TABLE OptionQuestion');
        $this->addSql('DROP TABLE ParticipationFormation');
        $this->addSql('DROP TABLE Publication');
        $this->addSql('DROP TABLE Question');
        $this->addSql('DROP TABLE Quiz');
        $this->addSql('DROP TABLE ResultatQuiz');
        $this->addSql('DROP TABLE User');
        $this->addSql('DROP TABLE challenge');
        $this->addSql('DROP TABLE club');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE demande_adhesion');
        $this->addSql('DROP TABLE demande_club');
        $this->addSql('DROP TABLE demande_membre');
        $this->addSql('DROP TABLE favori');
        $this->addSql('DROP TABLE groupe');
        $this->addSql('DROP TABLE livrable_challenge');
        $this->addSql('DROP TABLE membre_groupe');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE participation');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
