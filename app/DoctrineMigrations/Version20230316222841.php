<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230316222841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
                ALTER TABLE user
                ADD first_name VARCHAR(255) DEFAULT NULL,
                ADD last_name VARCHAR(255) DEFAULT NULL,
                ADD position VARCHAR(255) DEFAULT NULL
                ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP first_name, DROP last_name, DROP position');
    }
}
