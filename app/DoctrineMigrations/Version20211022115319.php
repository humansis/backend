<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211022115319 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'CREATE TABLE synchronization_batch (id INT AUTO_INCREMENT NOT NULL, created_by_user_id INT DEFAULT NULL, validation_type ENUM(\'Deposit\', \'Purchase\') NOT NULL COMMENT \'(DC2Type:enum_synchronization_batch_validation_type)\', state ENUM(\'Uploaded\', \'Correct\', \'Errors\', \'Archived\') NOT NULL COMMENT \'(DC2Type:enum_synchronization_batch_state)\', request_data JSON NOT NULL, violations JSON DEFAULT NULL, validated_at DATETIME DEFAULT NULL, source ENUM(\'Web\', \'Vendor\', \'User\', \'CLI\') DEFAULT NULL COMMENT \'(DC2Type:enum_source_type)\', created_at DATETIME DEFAULT NULL, INDEX IDX_8346B8467D182D95 (created_by_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE synchronization_batch ADD CONSTRAINT FK_8346B8467D182D95 FOREIGN KEY (created_by_user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE synchronization_batch');
    }
}
