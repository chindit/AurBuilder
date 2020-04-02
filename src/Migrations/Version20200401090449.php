<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200401090449 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE package_release (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, last_version VARCHAR(25) NOT NULL, new_version VARCHAR(25) NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE package ADD releases_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE package ADD CONSTRAINT FK_DE686795C9749AD6 FOREIGN KEY (releases_id) REFERENCES package_release (id)');
        $this->addSql('CREATE INDEX IDX_DE686795C9749AD6 ON package (releases_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE package DROP FOREIGN KEY FK_DE686795C9749AD6');
        $this->addSql('DROP TABLE package_release');
        $this->addSql('DROP INDEX IDX_DE686795C9749AD6 ON package');
        $this->addSql('ALTER TABLE package DROP releases_id');
    }
}
