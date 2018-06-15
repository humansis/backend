<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180523132621 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE distribution_beneficiary (id INT AUTO_INCREMENT NOT NULL, distribution_data_id INT DEFAULT NULL, project_beneficiary_id INT DEFAULT NULL, INDEX IDX_EA141F30D744EF8E (distribution_data_id), INDEX IDX_EA141F3057D9B02 (project_beneficiary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE commodity_distribution_beneficiary (id INT AUTO_INCREMENT NOT NULL, distribution_beneficiary_id INT DEFAULT NULL, commodity_id INT DEFAULT NULL, status VARCHAR(255) NOT NULL, INDEX IDX_2C186A1395AAFAA9 (distribution_beneficiary_id), INDEX IDX_2C186A13B4ACC212 (commodity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE distribution_data (id INT AUTO_INCREMENT NOT NULL, location_id INT DEFAULT NULL, project_id INT DEFAULT NULL, selection_criteria_id INT DEFAULT NULL, name VARCHAR(45) NOT NULL, UpdatedOn DATETIME NOT NULL, INDEX IDX_A54E7FD764D218E (location_id), INDEX IDX_A54E7FD7166D1F9C (project_id), INDEX IDX_A54E7FD71376EC6E (selection_criteria_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE selection_criteria (id INT AUTO_INCREMENT NOT NULL, table_string VARCHAR(255) NOT NULL, field_string VARCHAR(255) NOT NULL, value_string VARCHAR(255) NOT NULL, condition_string VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE location (id INT AUTO_INCREMENT NOT NULL, country_iso3 VARCHAR(45) NOT NULL, adm1 VARCHAR(255) NOT NULL, adm2 VARCHAR(255) NOT NULL, adm3 VARCHAR(255) NOT NULL, adm4 VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_beneficiary (id INT AUTO_INCREMENT NOT NULL, beneficiary_id INT DEFAULT NULL, project_id INT DEFAULT NULL, INDEX IDX_B270B391ECCAAFA0 (beneficiary_id), INDEX IDX_B270B391166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE commodity (id INT AUTO_INCREMENT NOT NULL, distribution_data_id INT DEFAULT NULL, modality VARCHAR(45) NOT NULL, type VARCHAR(45) NOT NULL, unit VARCHAR(45) NOT NULL, value DOUBLE PRECISION NOT NULL, conditions VARCHAR(45) NOT NULL, INDEX IDX_5E8D2F74D744EF8E (distribution_data_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE distribution_beneficiary ADD CONSTRAINT FK_EA141F30D744EF8E FOREIGN KEY (distribution_data_id) REFERENCES distribution_data (id)');
        $this->addSql('ALTER TABLE distribution_beneficiary ADD CONSTRAINT FK_EA141F3057D9B02 FOREIGN KEY (project_beneficiary_id) REFERENCES project_beneficiary (id)');
        $this->addSql('ALTER TABLE commodity_distribution_beneficiary ADD CONSTRAINT FK_2C186A1395AAFAA9 FOREIGN KEY (distribution_beneficiary_id) REFERENCES distribution_beneficiary (id)');
        $this->addSql('ALTER TABLE commodity_distribution_beneficiary ADD CONSTRAINT FK_2C186A13B4ACC212 FOREIGN KEY (commodity_id) REFERENCES commodity (id)');
        $this->addSql('ALTER TABLE distribution_data ADD CONSTRAINT FK_A54E7FD764D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('ALTER TABLE distribution_data ADD CONSTRAINT FK_A54E7FD7166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE distribution_data ADD CONSTRAINT FK_A54E7FD71376EC6E FOREIGN KEY (selection_criteria_id) REFERENCES selection_criteria (id)');
        $this->addSql('ALTER TABLE project_beneficiary ADD CONSTRAINT FK_B270B391ECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES beneficiary (id)');
        $this->addSql('ALTER TABLE project_beneficiary ADD CONSTRAINT FK_B270B391166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE commodity ADD CONSTRAINT FK_5E8D2F74D744EF8E FOREIGN KEY (distribution_data_id) REFERENCES distribution_data (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE commodity_distribution_beneficiary DROP FOREIGN KEY FK_2C186A1395AAFAA9');
        $this->addSql('ALTER TABLE distribution_beneficiary DROP FOREIGN KEY FK_EA141F30D744EF8E');
        $this->addSql('ALTER TABLE commodity DROP FOREIGN KEY FK_5E8D2F74D744EF8E');
        $this->addSql('ALTER TABLE distribution_data DROP FOREIGN KEY FK_A54E7FD71376EC6E');
        $this->addSql('ALTER TABLE distribution_data DROP FOREIGN KEY FK_A54E7FD764D218E');
        $this->addSql('ALTER TABLE distribution_beneficiary DROP FOREIGN KEY FK_EA141F3057D9B02');
        $this->addSql('ALTER TABLE commodity_distribution_beneficiary DROP FOREIGN KEY FK_2C186A13B4ACC212');
        $this->addSql('DROP TABLE distribution_beneficiary');
        $this->addSql('DROP TABLE commodity_distribution_beneficiary');
        $this->addSql('DROP TABLE distribution_data');
        $this->addSql('DROP TABLE selection_criteria');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE project_beneficiary');
        $this->addSql('DROP TABLE commodity');
    }
}
