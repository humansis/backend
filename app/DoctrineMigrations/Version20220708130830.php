<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220708130830 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            "ALTER TABLE `import` CHANGE `state` `state` enum('New','Uploading','Upload Failed','Integrity Checking','Integrity Check Correct','Integrity Check Failed','Identity Checking','Identity Check Correct','Identity Check Failed','Similarity Checking','Similarity Check Correct','Similarity Check Failed','Importing','Finished','Canceled') COLLATE 'utf8_unicode_ci' NOT NULL COMMENT '(DC2Type:enum_import_state)' AFTER `notes`;"
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            "ALTER TABLE `import` CHANGE `state` `state` enum('New','Integrity Checking','Integrity Check Correct','Integrity Check Failed','Identity Checking','Identity Check Correct','Identity Check Failed','Similarity Checking','Similarity Check Correct','Similarity Check Failed','Importing','Finished','Canceled') COLLATE 'utf8_unicode_ci' NOT NULL COMMENT '(DC2Type:enum_import_state)' AFTER `notes`"
        );
    }
}
