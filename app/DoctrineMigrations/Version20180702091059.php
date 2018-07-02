<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180702091059 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE reporting_value (id INT AUTO_INCREMENT NOT NULL, value VARCHAR(255) NOT NULL, unity VARCHAR(255) NOT NULL, creationDate DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reporting_country (id INT AUTO_INCREMENT NOT NULL, indicator_id INT DEFAULT NULL, value_id INT NOT NULL, country VARCHAR(255) NOT NULL, INDEX IDX_8522EACE4402854A (indicator_id), INDEX IDX_8522EACEF920BBA2 (value_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reporting_project (id INT AUTO_INCREMENT NOT NULL, project_id INT DEFAULT NULL, indicator_id INT DEFAULT NULL, value_id INT NOT NULL, INDEX IDX_F9E2F346166D1F9C (project_id), INDEX IDX_F9E2F3464402854A (indicator_id), INDEX IDX_F9E2F346F920BBA2 (value_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reporting_indicator (id INT AUTO_INCREMENT NOT NULL, reference VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, filtres LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', graphique VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_158D0C7177153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reporting_distribution (id INT AUTO_INCREMENT NOT NULL, distribution_id INT DEFAULT NULL, indicator_id INT DEFAULT NULL, value_id INT NOT NULL, INDEX IDX_EC84C5186EB6DDB5 (distribution_id), INDEX IDX_EC84C5184402854A (indicator_id), INDEX IDX_EC84C518F920BBA2 (value_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reporting_country ADD CONSTRAINT FK_8522EACE4402854A FOREIGN KEY (indicator_id) REFERENCES reporting_indicator (id)');
        $this->addSql('ALTER TABLE reporting_country ADD CONSTRAINT FK_8522EACEF920BBA2 FOREIGN KEY (value_id) REFERENCES reporting_value (id)');
        $this->addSql('ALTER TABLE reporting_project ADD CONSTRAINT FK_F9E2F346166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE reporting_project ADD CONSTRAINT FK_F9E2F3464402854A FOREIGN KEY (indicator_id) REFERENCES reporting_indicator (id)');
        $this->addSql('ALTER TABLE reporting_project ADD CONSTRAINT FK_F9E2F346F920BBA2 FOREIGN KEY (value_id) REFERENCES reporting_value (id)');
        $this->addSql('ALTER TABLE reporting_distribution ADD CONSTRAINT FK_EC84C5186EB6DDB5 FOREIGN KEY (distribution_id) REFERENCES distribution_data (id)');
        $this->addSql('ALTER TABLE reporting_distribution ADD CONSTRAINT FK_EC84C5184402854A FOREIGN KEY (indicator_id) REFERENCES reporting_indicator (id)');
        $this->addSql('ALTER TABLE reporting_distribution ADD CONSTRAINT FK_EC84C518F920BBA2 FOREIGN KEY (value_id) REFERENCES reporting_value (id)');
        $this->addSql('ALTER TABLE distribution_data ADD validated TINYINT(1) DEFAULT \'0\' NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE reporting_country DROP FOREIGN KEY FK_8522EACEF920BBA2');
        $this->addSql('ALTER TABLE reporting_project DROP FOREIGN KEY FK_F9E2F346F920BBA2');
        $this->addSql('ALTER TABLE reporting_distribution DROP FOREIGN KEY FK_EC84C518F920BBA2');
        $this->addSql('ALTER TABLE reporting_country DROP FOREIGN KEY FK_8522EACE4402854A');
        $this->addSql('ALTER TABLE reporting_project DROP FOREIGN KEY FK_F9E2F3464402854A');
        $this->addSql('ALTER TABLE reporting_distribution DROP FOREIGN KEY FK_EC84C5184402854A');
        $this->addSql('DROP TABLE reporting_value');
        $this->addSql('DROP TABLE reporting_country');
        $this->addSql('DROP TABLE reporting_project');
        $this->addSql('DROP TABLE reporting_indicator');
        $this->addSql('DROP TABLE reporting_distribution');
        $this->addSql('ALTER TABLE distribution_data DROP validated');
    }
}
