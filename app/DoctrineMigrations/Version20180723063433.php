<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180723063433 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE household CHANGE livelihood livelihood INT DEFAULT NULL, CHANGE notes notes VARCHAR(255) DEFAULT NULL, CHANGE latitude latitude VARCHAR(45) DEFAULT NULL, CHANGE longitude longitude VARCHAR(45) DEFAULT NULL');
        $this->addSql('ALTER TABLE country_specific_answer CHANGE answer answer VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE country_specific_answer CHANGE answer answer VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE household CHANGE livelihood livelihood INT NOT NULL, CHANGE notes notes VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE latitude latitude VARCHAR(45) NOT NULL COLLATE utf8_unicode_ci, CHANGE longitude longitude VARCHAR(45) NOT NULL COLLATE utf8_unicode_ci');
    }
}
