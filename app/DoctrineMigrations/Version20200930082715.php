<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200930082715 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('RENAME TABLE distribution_data TO assistance;');
        $this->addSql('ALTER TABLE assistance ADD CONSTRAINT FK_1B4F85F264D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('ALTER TABLE assistance ADD CONSTRAINT FK_1B4F85F2166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');

        $this->addSql('ALTER TABLE distribution_beneficiary DROP FOREIGN KEY FK_EA141F30D744EF8E');
        $this->addSql('DROP INDEX IDX_EA141F30D744EF8E ON distribution_beneficiary');
        $this->addSql('ALTER TABLE distribution_beneficiary CHANGE distribution_data_id assistance_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE distribution_beneficiary ADD CONSTRAINT FK_EA141F307096529A FOREIGN KEY (assistance_id) REFERENCES assistance (id)');
        $this->addSql('CREATE INDEX IDX_EA141F307096529A ON distribution_beneficiary (assistance_id)');

        $this->addSql('ALTER TABLE selection_criteria DROP FOREIGN KEY FK_61BAEEC9D744EF8E');
        $this->addSql('DROP INDEX IDX_61BAEEC9D744EF8E ON selection_criteria');
        $this->addSql('ALTER TABLE selection_criteria CHANGE distribution_data_id assistance_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE selection_criteria ADD CONSTRAINT FK_61BAEEC97096529A FOREIGN KEY (assistance_id) REFERENCES assistance (id)');
        $this->addSql('CREATE INDEX IDX_61BAEEC97096529A ON selection_criteria (assistance_id)');

        $this->addSql('ALTER TABLE commodity DROP FOREIGN KEY FK_5E8D2F74D744EF8E');
        $this->addSql('DROP INDEX IDX_5E8D2F74D744EF8E ON commodity');
        $this->addSql('ALTER TABLE commodity CHANGE distribution_data_id assistance_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commodity ADD CONSTRAINT FK_5E8D2F747096529A FOREIGN KEY (assistance_id) REFERENCES assistance (id)');
        $this->addSql('CREATE INDEX IDX_5E8D2F747096529A ON commodity (assistance_id)');

        $this->addSql('ALTER TABLE reporting_distribution DROP FOREIGN KEY FK_EC84C5186EB6DDB5');
        $this->addSql('ALTER TABLE reporting_distribution CHANGE distribution_id assistance_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reporting_distribution ADD CONSTRAINT FK_EC84C5186EB6DDB5 FOREIGN KEY (assistance_id) REFERENCES assistance (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE distribution_beneficiary DROP FOREIGN KEY FK_EA141F307096529A');
        $this->addSql('ALTER TABLE selection_criteria DROP FOREIGN KEY FK_61BAEEC97096529A');
        $this->addSql('ALTER TABLE commodity DROP FOREIGN KEY FK_5E8D2F747096529A');
        $this->addSql('ALTER TABLE reporting_distribution DROP FOREIGN KEY FK_EC84C5186EB6DDB5');
        $this->addSql(
            'CREATE TABLE distribution_data (id INT AUTO_INCREMENT NOT NULL, location_id INT DEFAULT NULL, project_id INT DEFAULT NULL, name VARCHAR(45) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, UpdatedOn DATETIME NOT NULL, date_distribution DATE NOT NULL, archived TINYINT(1) DEFAULT \'0\' NOT NULL, validated TINYINT(1) DEFAULT \'0\' NOT NULL, target_type INT DEFAULT NULL, completed TINYINT(1) DEFAULT \'0\' NOT NULL, assistance_type ENUM(\'activity\', \'distribution\') CHARACTER SET utf8 DEFAULT \'distribution\' NOT NULL COLLATE `utf8_unicode_ci` COMMENT \'(DC2Type:enum_assistance_type)\', INDEX IDX_A54E7FD7166D1F9C (project_id), INDEX IDX_A54E7FD764D218E (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' '
        );
        $this->addSql('ALTER TABLE distribution_data ADD CONSTRAINT FK_A54E7FD7166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE distribution_data ADD CONSTRAINT FK_A54E7FD764D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('DROP TABLE assistance');
        $this->addSql('ALTER TABLE beneficiary CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('DROP INDEX IDX_5E8D2F747096529A ON commodity');
        $this->addSql('ALTER TABLE commodity CHANGE assistance_id distribution_data_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commodity ADD CONSTRAINT FK_5E8D2F74D744EF8E FOREIGN KEY (distribution_data_id) REFERENCES distribution_data (id)');
        $this->addSql('CREATE INDEX IDX_5E8D2F74D744EF8E ON commodity (distribution_data_id)');
        $this->addSql('ALTER TABLE community CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('DROP INDEX IDX_EA141F307096529A ON distribution_beneficiary');
        $this->addSql('ALTER TABLE distribution_beneficiary CHANGE assistance_id distribution_data_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE distribution_beneficiary ADD CONSTRAINT FK_EA141F30D744EF8E FOREIGN KEY (distribution_data_id) REFERENCES distribution_data (id)');
        $this->addSql('CREATE INDEX IDX_EA141F30D744EF8E ON distribution_beneficiary (distribution_data_id)');
        $this->addSql('ALTER TABLE household CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE institution CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE reporting_distribution DROP FOREIGN KEY FK_EC84C5186EB6DDB5');
        $this->addSql('ALTER TABLE reporting_distribution ADD CONSTRAINT FK_EC84C5186EB6DDB5 FOREIGN KEY (distribution_id) REFERENCES distribution_data (id)');
        $this->addSql('DROP INDEX IDX_61BAEEC97096529A ON selection_criteria');
        $this->addSql('ALTER TABLE selection_criteria CHANGE assistance_id distribution_data_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE selection_criteria ADD CONSTRAINT FK_61BAEEC9D744EF8E FOREIGN KEY (distribution_data_id) REFERENCES distribution_data (id)');
        $this->addSql('CREATE INDEX IDX_61BAEEC9D744EF8E ON selection_criteria (distribution_data_id)');
    }
}
