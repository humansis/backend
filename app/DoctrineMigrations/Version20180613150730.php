<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180613150730 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE beneficiary DROP FOREIGN KEY FK_7ABF446A20D609B2');
        $this->addSql('ALTER TABLE country_specific_answer DROP FOREIGN KEY FK_4680BB3020D609B2');
        $this->addSql('ALTER TABLE beneficiary DROP FOREIGN KEY FK_7ABF446A6F215AD8');
        $this->addSql('ALTER TABLE h_h_member DROP FOREIGN KEY FK_9BE4DBDE6F215AD8');
        $this->addSql('ALTER TABLE h_h_member DROP FOREIGN KEY FK_9BE4DBDEECCAAFA0');
        $this->addSql('CREATE TABLE household (id INT AUTO_INCREMENT NOT NULL, location_id INT DEFAULT NULL, address_street VARCHAR(255) NOT NULL, address_number VARCHAR(255) NOT NULL, address_postcode VARCHAR(255) NOT NULL, livelihood INT NOT NULL, notes VARCHAR(255) NOT NULL, lat VARCHAR(45) NOT NULL, `long` VARCHAR(45) NOT NULL, INDEX IDX_54C32FC064D218E (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE beneficiary_vulnerability_criterion (beneficiary_id INT NOT NULL, vulnerability_criterion_id INT NOT NULL, INDEX IDX_566B5C7ECCAAFA0 (beneficiary_id), INDEX IDX_566B5C77255F7BA (vulnerability_criterion_id), PRIMARY KEY(beneficiary_id, vulnerability_criterion_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vulnerability_criterion (id INT AUTO_INCREMENT NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE household ADD CONSTRAINT FK_54C32FC064D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('ALTER TABLE beneficiary_vulnerability_criterion ADD CONSTRAINT FK_566B5C7ECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES beneficiary (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE beneficiary_vulnerability_criterion ADD CONSTRAINT FK_566B5C77255F7BA FOREIGN KEY (vulnerability_criterion_id) REFERENCES vulnerability_criterion (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE beneficiary_profile');
        $this->addSql('DROP TABLE h_h_member');
        $this->addSql('DROP TABLE vulnerability_criteria');
        $this->addSql('DROP INDEX IDX_4680BB3020D609B2 ON country_specific_answer');
        $this->addSql('ALTER TABLE country_specific_answer CHANGE beneficiary_profile_id household_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE country_specific_answer ADD CONSTRAINT FK_4680BB30E79FF843 FOREIGN KEY (household_id) REFERENCES household (id)');
        $this->addSql('CREATE INDEX IDX_4680BB30E79FF843 ON country_specific_answer (household_id)');
        $this->addSql('DROP INDEX IDX_7ABF446A20D609B2 ON beneficiary');
        $this->addSql('DROP INDEX IDX_7ABF446A6F215AD8 ON beneficiary');
        $this->addSql('ALTER TABLE beneficiary ADD household_id INT DEFAULT NULL, ADD household_head TINYINT(1) NOT NULL, ADD photo VARCHAR(255) NOT NULL, DROP beneficiary_profile_id, DROP vulnerability_criteria_id, CHANGE updatedat updated_on DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE beneficiary ADD CONSTRAINT FK_7ABF446AE79FF843 FOREIGN KEY (household_id) REFERENCES household (id)');
        $this->addSql('CREATE INDEX IDX_7ABF446AE79FF843 ON beneficiary (household_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE country_specific_answer DROP FOREIGN KEY FK_4680BB30E79FF843');
        $this->addSql('ALTER TABLE beneficiary DROP FOREIGN KEY FK_7ABF446AE79FF843');
        $this->addSql('ALTER TABLE beneficiary_vulnerability_criterion DROP FOREIGN KEY FK_566B5C77255F7BA');
        $this->addSql('CREATE TABLE beneficiary_profile (id INT AUTO_INCREMENT NOT NULL, location_id INT DEFAULT NULL, photo VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, address_street VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, address_number VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, address_postcode VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, livelihood INT NOT NULL, notes VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, INDEX IDX_5C8EFFA564D218E (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE h_h_member (id INT AUTO_INCREMENT NOT NULL, vulnerability_criteria_id INT DEFAULT NULL, beneficiary_id INT DEFAULT NULL, gender VARCHAR(1) NOT NULL COLLATE utf8_unicode_ci, dateOfBirth DATE NOT NULL, INDEX IDX_9BE4DBDE6F215AD8 (vulnerability_criteria_id), INDEX IDX_9BE4DBDEECCAAFA0 (beneficiary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vulnerability_criteria (id INT AUTO_INCREMENT NOT NULL, pregnant TINYINT(1) DEFAULT NULL, lactating TINYINT(1) DEFAULT NULL, disabled TINYINT(1) DEFAULT NULL, malnourished TINYINT(1) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE beneficiary_profile ADD CONSTRAINT FK_5C8EFFA564D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('ALTER TABLE h_h_member ADD CONSTRAINT FK_9BE4DBDE6F215AD8 FOREIGN KEY (vulnerability_criteria_id) REFERENCES vulnerability_criteria (id)');
        $this->addSql('ALTER TABLE h_h_member ADD CONSTRAINT FK_9BE4DBDEECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES vulnerability_criteria (id)');
        $this->addSql('DROP TABLE household');
        $this->addSql('DROP TABLE beneficiary_vulnerability_criterion');
        $this->addSql('DROP TABLE vulnerability_criterion');
        $this->addSql('DROP INDEX IDX_7ABF446AE79FF843 ON beneficiary');
        $this->addSql('ALTER TABLE beneficiary ADD vulnerability_criteria_id INT DEFAULT NULL, DROP household_head, DROP photo, CHANGE household_id beneficiary_profile_id INT DEFAULT NULL, CHANGE updated_on updatedAt DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE beneficiary ADD CONSTRAINT FK_7ABF446A20D609B2 FOREIGN KEY (beneficiary_profile_id) REFERENCES beneficiary_profile (id)');
        $this->addSql('ALTER TABLE beneficiary ADD CONSTRAINT FK_7ABF446A6F215AD8 FOREIGN KEY (vulnerability_criteria_id) REFERENCES vulnerability_criteria (id)');
        $this->addSql('CREATE INDEX IDX_7ABF446A20D609B2 ON beneficiary (beneficiary_profile_id)');
        $this->addSql('CREATE INDEX IDX_7ABF446A6F215AD8 ON beneficiary (vulnerability_criteria_id)');
        $this->addSql('DROP INDEX IDX_4680BB30E79FF843 ON country_specific_answer');
        $this->addSql('ALTER TABLE country_specific_answer CHANGE household_id beneficiary_profile_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE country_specific_answer ADD CONSTRAINT FK_4680BB3020D609B2 FOREIGN KEY (beneficiary_profile_id) REFERENCES beneficiary_profile (id)');
        $this->addSql('CREATE INDEX IDX_4680BB3020D609B2 ON country_specific_answer (beneficiary_profile_id)');
    }
}
