<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionFixClubMembreMapping extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Corrige la table club_membre pour référencer id_Club au lieu de id';
    }

    public function up(Schema $schema): void
    {
        // Renomme la colonne club_id en club_id_Club
        $this->addSql('ALTER TABLE club_membre CHANGE club_id club_id_Club INT NOT NULL');
        // Supprime l’ancienne clé étrangère
        $this->addSql('ALTER TABLE club_membre DROP FOREIGN KEY FK_club_membre_club_id');
        // Ajoute la nouvelle clé étrangère
        $this->addSql('ALTER TABLE club_membre ADD CONSTRAINT FK_club_membre_club_id_Club FOREIGN KEY (club_id_Club) REFERENCES club(id_Club) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Revert changes
        $this->addSql('ALTER TABLE club_membre DROP FOREIGN KEY FK_club_membre_club_id_Club');
        $this->addSql('ALTER TABLE club_membre CHANGE club_id_Club club_id INT NOT NULL');
        $this->addSql('ALTER TABLE club_membre ADD CONSTRAINT FK_club_membre_club_id FOREIGN KEY (club_id) REFERENCES club(id_Club) ON DELETE CASCADE');
    }
}
