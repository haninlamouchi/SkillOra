<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211093612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE challenge CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE fichier_cahier_charges fichier_cahier_charges VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE membre_groupe ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE membre_groupe ADD CONSTRAINT FK_9EB01998A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_9EB01998A76ED395 ON membre_groupe (user_id)');
        $this->addSql('ALTER TABLE user CHANGE date_naissance date_naissance DATE DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE challenge CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE fichier_cahier_charges fichier_cahier_charges VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE membre_groupe DROP FOREIGN KEY FK_9EB01998A76ED395');
        $this->addSql('DROP INDEX IDX_9EB01998A76ED395 ON membre_groupe');
        $this->addSql('ALTER TABLE membre_groupe DROP user_id');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('DROP INDEX UNIQ_8D93D649E7927C74 ON user');
        $this->addSql('ALTER TABLE user CHANGE date_naissance date_naissance DATE DEFAULT \'NULL\'');
    }
}
