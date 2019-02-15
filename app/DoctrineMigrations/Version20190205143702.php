<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190205143702 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE product ADD unit VARCHAR(255) NOT NULL, ADD image VARCHAR(255) NOT NULL, CHANGE description name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE booklet ADD archived TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE voucher CHANGE individual_value value INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE booklet DROP archived');
        $this->addSql('ALTER TABLE product ADD description VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, DROP name, DROP unit, DROP image');
        $this->addSql('ALTER TABLE voucher CHANGE value individual_value INT NOT NULL');
    }
}
