<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210000020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration des champs typecontenu et status de Publication vers ENUM';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE challenge CHANGE date_debut date_debut DATE DEFAULT NULL, CHANGE date_fin date_fin DATE DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE fichier_cahier_charges fichier_cahier_charges VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE club CHANGE logo logo VARCHAR(255) DEFAULT NULL, CHANGE date_creation date_creation DATE DEFAULT NULL, CHANGE email email VARCHAR(150) DEFAULT NULL, CHANGE site_web site_web VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE formation CHANGE date_debut date_debut DATE DEFAULT NULL, CHANGE date_fin date_fin DATE DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE lien_ressources lien_ressources VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE livrablechallenge CHANGE fichier fichier VARCHAR(255) DEFAULT NULL, CHANGE date_soumission date_soumission DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE membregroupe CHANGE role role VARCHAR(50) DEFAULT NULL');
        
        // ✅✅✅ CORRECTION POUR PUBLICATION - CONVERSION VERS ENUM ✅✅✅
        // Nettoyer les données avant conversion
        $this->addSql("UPDATE publication SET status = 'en attente' WHERE status IS NULL OR status = ''");
        
        // Convertir typecontenu en ENUM
        $this->addSql("ALTER TABLE publication CHANGE typecontenu typecontenu ENUM('image', 'video', 'texte') NOT NULL");
        
        // Convertir status en ENUM avec valeur par défaut
        $this->addSql("ALTER TABLE publication CHANGE status status ENUM('en attente', 'publié') NOT NULL DEFAULT 'en attente'");
        
        $this->addSql('ALTER TABLE user CHANGE role role ENUM(\'admin\', \'responsable_club\', \'etudiant\'), CHANGE telephone telephone VARCHAR(20) DEFAULT NULL, CHANGE photo photo VARCHAR(255) DEFAULT NULL, CHANGE date_naissance date_naissance DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Challenge CHANGE date_debut date_debut DATE DEFAULT \'NULL\', CHANGE date_fin date_fin DATE DEFAULT \'NULL\', CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE fichier_cahier_charges fichier_cahier_charges VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE Club CHANGE logo logo VARCHAR(255) DEFAULT \'NULL\', CHANGE date_creation date_creation DATE DEFAULT \'NULL\', CHANGE email email VARCHAR(150) DEFAULT \'NULL\', CHANGE site_web site_web VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE Formation CHANGE date_debut date_debut DATE DEFAULT \'NULL\', CHANGE date_fin date_fin DATE DEFAULT \'NULL\', CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE lien_ressources lien_ressources VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE LivrableChallenge CHANGE fichier fichier VARCHAR(255) DEFAULT \'NULL\', CHANGE date_soumission date_soumission DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE MembreGroupe CHANGE role role VARCHAR(50) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        
        // ✅✅✅ CORRECTION POUR PUBLICATION - RETOUR VERS VARCHAR ✅✅✅
        $this->addSql('ALTER TABLE Publication CHANGE typecontenu typecontenu VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE Publication CHANGE status status VARCHAR(50) DEFAULT NULL');
        
        $this->addSql('ALTER TABLE User CHANGE role role ENUM(\'admin\', \'responsable_club\', \'etudiant\') DEFAULT \'NULL\', CHANGE telephone telephone VARCHAR(20) DEFAULT \'NULL\', CHANGE photo photo VARCHAR(255) DEFAULT \'NULL\', CHANGE date_naissance date_naissance DATE DEFAULT \'NULL\'');
    }
}