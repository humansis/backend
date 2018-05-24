<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180523125741 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE national_id (id INT AUTO_INCREMENT NOT NULL, beneficiary_id INT DEFAULT NULL, id_number VARCHAR(45) NOT NULL, id_type VARCHAR(45) NOT NULL, INDEX IDX_36491297ECCAAFA0 (beneficiary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE h_h_member (id INT AUTO_INCREMENT NOT NULL, vulnerability_criteria_id INT DEFAULT NULL, beneficiary_id INT DEFAULT NULL, gender VARCHAR(1) NOT NULL, dateOfBirth DATE NOT NULL, INDEX IDX_9BE4DBDE6F215AD8 (vulnerability_criteria_id), INDEX IDX_9BE4DBDEECCAAFA0 (beneficiary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE country_specific (id INT AUTO_INCREMENT NOT NULL, field VARCHAR(45) NOT NULL, type VARCHAR(45) NOT NULL, country_iso3 VARCHAR(45) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE country_specific_answer (id INT AUTO_INCREMENT NOT NULL, country_specific_id INT DEFAULT NULL, beneficiary_profile_id INT DEFAULT NULL, answer VARCHAR(255) NOT NULL, INDEX IDX_4680BB30433BFD7C (country_specific_id), INDEX IDX_4680BB3020D609B2 (beneficiary_profile_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE beneficiary_profile (id INT AUTO_INCREMENT NOT NULL, photo VARCHAR(255) NOT NULL, address_street VARCHAR(255) NOT NULL, address_number VARCHAR(255) NOT NULL, address_postcode VARCHAR(255) NOT NULL, livelihood INT NOT NULL, notes VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE phone (id INT AUTO_INCREMENT NOT NULL, beneficiary_id INT DEFAULT NULL, number VARCHAR(45) NOT NULL, type VARCHAR(45) NOT NULL, INDEX IDX_444F97DDECCAAFA0 (beneficiary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE national_id ADD CONSTRAINT FK_36491297ECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES beneficiary (id)');
        $this->addSql('ALTER TABLE h_h_member ADD CONSTRAINT FK_9BE4DBDE6F215AD8 FOREIGN KEY (vulnerability_criteria_id) REFERENCES vulnerability_criteria (id)');
        $this->addSql('ALTER TABLE h_h_member ADD CONSTRAINT FK_9BE4DBDEECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES vulnerability_criteria (id)');
        $this->addSql('ALTER TABLE country_specific_answer ADD CONSTRAINT FK_4680BB30433BFD7C FOREIGN KEY (country_specific_id) REFERENCES country_specific (id)');
        $this->addSql('ALTER TABLE country_specific_answer ADD CONSTRAINT FK_4680BB3020D609B2 FOREIGN KEY (beneficiary_profile_id) REFERENCES beneficiary_profile (id)');
        $this->addSql('ALTER TABLE phone ADD CONSTRAINT FK_444F97DDECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES beneficiary (id)');
        $this->addSql('ALTER TABLE beneficiary ADD beneficiary_profile_id INT DEFAULT NULL, ADD vulnerability_criteria_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE beneficiary ADD CONSTRAINT FK_7ABF446A20D609B2 FOREIGN KEY (beneficiary_profile_id) REFERENCES beneficiary_profile (id)');
        $this->addSql('ALTER TABLE beneficiary ADD CONSTRAINT FK_7ABF446A6F215AD8 FOREIGN KEY (vulnerability_criteria_id) REFERENCES vulnerability_criteria (id)');
        $this->addSql('CREATE INDEX IDX_7ABF446A20D609B2 ON beneficiary (beneficiary_profile_id)');
        $this->addSql('CREATE INDEX IDX_7ABF446A6F215AD8 ON beneficiary (vulnerability_criteria_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE country_specific_answer DROP FOREIGN KEY FK_4680BB30433BFD7C');
        $this->addSql('ALTER TABLE country_specific_answer DROP FOREIGN KEY FK_4680BB3020D609B2');
        $this->addSql('ALTER TABLE beneficiary DROP FOREIGN KEY FK_7ABF446A20D609B2');
        $this->addSql('DROP TABLE national_id');
        $this->addSql('DROP TABLE h_h_member');
        $this->addSql('DROP TABLE country_specific');
        $this->addSql('DROP TABLE country_specific_answer');
        $this->addSql('DROP TABLE beneficiary_profile');
        $this->addSql('DROP TABLE phone');
        $this->addSql('ALTER TABLE beneficiary DROP FOREIGN KEY FK_7ABF446A6F215AD8');
        $this->addSql('DROP INDEX IDX_7ABF446A20D609B2 ON beneficiary');
        $this->addSql('DROP INDEX IDX_7ABF446A6F215AD8 ON beneficiary');
        $this->addSql('ALTER TABLE beneficiary DROP beneficiary_profile_id, DROP vulnerability_criteria_id');
    }
}
