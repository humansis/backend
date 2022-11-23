<?php

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20220909071033 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE location CHANGE COLUMN countryISO3 iso3 CHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE scoring_blueprint MODIFY COLUMN iso3 CHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE project MODIFY COLUMN iso3 CHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE country_specific CHANGE COLUMN country_iso3 iso3 CHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE booklet CHANGE COLUMN country_iso3 iso3 CHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE product CHANGE COLUMN countryISO3 iso3 CHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE import MODIFY COLUMN iso3 CHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE user_country MODIFY COLUMN iso3 CHAR(3) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(true, 'Can not be downgraded');
    }
}
