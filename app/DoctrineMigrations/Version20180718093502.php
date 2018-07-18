<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180718093502 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE location CHANGE adm1 adm1 VARCHAR(255) DEFAULT NULL, CHANGE adm2 adm2 VARCHAR(255) DEFAULT NULL, CHANGE adm3 adm3 VARCHAR(255) DEFAULT NULL, CHANGE adm4 adm4 VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE location CHANGE adm1 adm1 VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE adm2 adm2 VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE adm3 adm3 VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE adm4 adm4 VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');
    }
}
