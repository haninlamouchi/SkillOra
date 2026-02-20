<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218115225 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Club.nom, Club.responsable (OneToOne → User), and club_membre join table (Club ManyToMany User)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE club_membre (club_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_D37B47A361190A32 (club_id), INDEX IDX_D37B47A3A76ED395 (user_id), PRIMARY KEY (club_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE club_membre ADD CONSTRAINT FK_D37B47A361190A32 FOREIGN KEY (club_id) REFERENCES club (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE club_membre ADD CONSTRAINT FK_D37B47A3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE club ADD nom VARCHAR(150) NOT NULL, ADD responsable_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE club ADD CONSTRAINT FK_B8EE387253C59D72 FOREIGN KEY (responsable_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B8EE387253C59D72 ON club (responsable_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE club_membre DROP FOREIGN KEY FK_D37B47A361190A32');
        $this->addSql('ALTER TABLE club_membre DROP FOREIGN KEY FK_D37B47A3A76ED395');
        $this->addSql('DROP TABLE club_membre');
        $this->addSql('ALTER TABLE club DROP FOREIGN KEY FK_B8EE387253C59D72');
        $this->addSql('DROP INDEX UNIQ_B8EE387253C59D72 ON club');
        $this->addSql('ALTER TABLE club DROP nom, DROP responsable_id');
    }
}
