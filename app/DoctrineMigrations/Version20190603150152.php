<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190603150152 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE household DROP FOREIGN KEY FK_54C32FC064D218E');
        $this->addSql('DROP INDEX IDX_54C32FC064D218E ON household');
        $this->addSql('ALTER TABLE household DROP location_id, DROP address_street, DROP address_number, DROP address_postcode');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE household ADD location_id INT DEFAULT NULL, ADD address_street VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, ADD address_number VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, ADD address_postcode VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE household ADD CONSTRAINT FK_54C32FC064D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('CREATE INDEX IDX_54C32FC064D218E ON household (location_id)');
    }
}
