<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201120122022 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE vulnerability_criterion ADD active TINYINT(1) NOT NULL DEFAULT 1');

        $this->addSql('UPDATE vulnerability_criterion SET active=0 WHERE field_string=\'nutritionalIssues\'');
        $this->addSql('INSERT INTO vulnerability_criterion (field_string) VALUES (\'chronicallyIll\')');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE vulnerability_criterion DROP active');
        $this->addSql('DELETE FROM vulnerability_criterion WHERE field_string=\'chronicallyIll\'');
    }
}
