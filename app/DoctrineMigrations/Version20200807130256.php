<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200807130256 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_7ABF446ACCFA12B8 ON abstract_beneficiary');

        $this->addSql(
            '
            CREATE TABLE abstract_beneficiary_project (
                abstract_beneficiary_id INT NOT NULL,
                project_id INT NOT NULL,
                INDEX IDX_80AC6109982A3051 (abstract_beneficiary_id),
                INDEX IDX_80AC6109166D1F9C (project_id),
                PRIMARY KEY(abstract_beneficiary_id, project_id),
                CONSTRAINT FK_80AC6109982A3051 FOREIGN KEY (abstract_beneficiary_id)
                    REFERENCES abstract_beneficiary (id)
                    ON DELETE CASCADE,
                CONSTRAINT FK_80AC6109166D1F9C FOREIGN KEY (project_id)
                    REFERENCES project (id)
                    ON DELETE CASCADE
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql('INSERT INTO abstract_beneficiary_project (abstract_beneficiary_id, project_id) SELECT household_id, project_id FROM `household_project`;');
        $this->addSql('DROP TABLE household_project');

        $this->addSql('ALTER TABLE abstract_beneficiary ADD archived TINYINT(1) DEFAULT 0 NOT NULL;');
        $this->addSql('UPDATE abstract_beneficiary ab INNER JOIN household hh ON ab.id=hh.id SET ab.archived=hh.archived;');
        $this->addSql('UPDATE abstract_beneficiary ab INNER JOIN community c ON ab.id=c.id SET ab.archived=c.archived;');
        $this->addSql('UPDATE abstract_beneficiary ab INNER JOIN institution i ON ab.id=i.id SET ab.archived=i.archived;');
        $this->addSql('ALTER TABLE household DROP archived');
        $this->addSql('ALTER TABLE community DROP archived');
        $this->addSql('ALTER TABLE institution DROP archived');

        $this->addSql('ALTER TABLE distribution_beneficiary DROP FOREIGN KEY FK_EA141F30ECCAAFA0');
        $this->addSql('ALTER TABLE distribution_beneficiary ADD CONSTRAINT FK_EA141F30ECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES abstract_beneficiary (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'CREATE TABLE household_project (household_id INT NOT NULL, project_id INT NOT NULL, INDEX IDX_42473AC0E79FF843 (household_id), INDEX IDX_42473AC0166D1F9C (project_id), PRIMARY KEY(household_id, project_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' '
        );
        $this->addSql('ALTER TABLE household_project ADD CONSTRAINT FK_42473AC0166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE household_project ADD CONSTRAINT FK_42473AC0E79FF843 FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE abstract_beneficiary_project');

        $this->addSql('ALTER TABLE abstract_beneficiary DROP archived, CHANGE bnf_type bnf_type VARCHAR(4) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7ABF446ACCFA12B8 ON abstract_beneficiary (id)');
        $this->addSql('ALTER TABLE beneficiary CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE community ADD archived TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE distribution_beneficiary DROP FOREIGN KEY FK_EA141F30ECCAAFA0');
        $this->addSql('ALTER TABLE distribution_beneficiary ADD CONSTRAINT FK_EA141F30ECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES beneficiary (id)');
        $this->addSql('ALTER TABLE household ADD archived TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE institution ADD archived TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE smartcard CHANGE created_at created_at DATETIME NOT NULL');
    }
}
