<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220204094906 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE import_beneficiary_duplicity RENAME import_household_duplicity');
        $this->addSql('ALTER TABLE import_beneficiary DROP FOREIGN KEY FK_FEC38F8AB03A8386');
        $this->addSql('DROP INDEX IDX_FEC38F8AB03A8386 ON import_beneficiary');
        $this->addSql('ALTER TABLE import_beneficiary CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_by_id created_by_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE import_beneficiary ADD CONSTRAINT FK_FEC38F8A7D182D95 FOREIGN KEY (created_by_user_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_FEC38F8A7D182D95 ON import_beneficiary (created_by_user_id)');
        $this->addSql('ALTER TABLE import_queue CHANGE identity_checked_at identity_checked_at DATETIME DEFAULT NULL, CHANGE similarity_checked_at similarity_checked_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE import DROP FOREIGN KEY FK_9D4ECE1DB03A8386');
        $this->addSql('DROP INDEX IDX_9D4ECE1DB03A8386 ON import');
        $this->addSql('ALTER TABLE import CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_by_id created_by_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE import ADD CONSTRAINT FK_9D4ECE1D7D182D95 FOREIGN KEY (created_by_user_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_9D4ECE1D7D182D95 ON import (created_by_user_id)');
        $this->addSql('ALTER TABLE import_file DROP FOREIGN KEY FK_61B3D890A76ED395');
        $this->addSql('DROP INDEX IDX_61B3D890A76ED395 ON import_file');
        $this->addSql('ALTER TABLE import_file CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE user_id created_by_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE import_file ADD CONSTRAINT FK_61B3D8907D182D95 FOREIGN KEY (created_by_user_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_61B3D8907D182D95 ON import_file (created_by_user_id)');
        $this->addSql('ALTER TABLE import_file RENAME INDEX fk_61b3d890b6a263d9 TO IDX_61B3D890B6A263D9');
        $this->addSql('ALTER TABLE import_queue_duplicity CHANGE decide_at decide_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE relief_package RENAME INDEX idx_82b31f853049e54a TO IDX_181280CD3049E54A');
        $this->addSql('ALTER TABLE import_invalid_file CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE invalid_queue_count invalid_queue_count INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(true, 'Cant be downgraded.');
    }
}
