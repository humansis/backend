<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230316225614 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE assistance ADD closed_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE assistance ADD CONSTRAINT FK_1B4F85F2E1FA7797 FOREIGN KEY (closed_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_1B4F85F2E1FA7797 ON assistance (closed_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE assistance DROP closed_by_id');
    }
}
