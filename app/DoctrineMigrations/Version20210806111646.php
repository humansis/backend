<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210806111646 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'ALTER TABLE import_file CHANGE expected_valid_columns expected_valid_columns LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE expected_missing_columns expected_missing_columns LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE unexpected_columns unexpected_columns LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\''
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'ALTER TABLE import_file CHANGE expected_valid_columns expected_valid_columns LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, CHANGE expected_missing_columns expected_missing_columns LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, CHANGE unexpected_columns unexpected_columns LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`'
        );
    }
}
