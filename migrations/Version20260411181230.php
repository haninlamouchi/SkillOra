<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260411181230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout cv, linkedin, updated_at à demande_adhesion';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_adhesion ADD cv VARCHAR(255) DEFAULT NULL, ADD linkedin VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_adhesion DROP COLUMN cv, DROP COLUMN linkedin, DROP COLUMN updated_at');
    }
}