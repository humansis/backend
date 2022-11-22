<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220107133220 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'ALTER TABLE import_queue ADD locked_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD locked_by VARCHAR(23) DEFAULT NULL, CHANGE identity_checked_at identity_checked_at DATETIME DEFAULT NULL, CHANGE similarity_checked_at similarity_checked_at DATETIME DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'ALTER TABLE import_queue DROP locked_at, DROP locked_by, CHANGE identity_checked_at identity_checked_at DATETIME DEFAULT NULL, CHANGE similarity_checked_at similarity_checked_at DATETIME DEFAULT NULL'
        );
    }
}
