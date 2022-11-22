<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220210131422 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE import ADD iso3 VARCHAR(3) DEFAULT NULL');
        $this->addSql(
            'CREATE TABLE import_project (import_id INT NOT NULL, project_id INT NOT NULL, INDEX IDX_3CA37902B6A263D9 (import_id), INDEX IDX_3CA37902166D1F9C (project_id), PRIMARY KEY(import_id, project_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE import_project ADD CONSTRAINT FK_3CA37902B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE import_project ADD CONSTRAINT FK_3CA37902166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE import DROP FOREIGN KEY FK_9D4ECE1D166D1F9C');
        $this->addSql('DROP INDEX IDX_9D4ECE1D166D1F9C ON import');
        $this->addSql('UPDATE import i INNER JOIN project p on i.project_id = p.id SET i.iso3=p.iso3');
        $this->addSql('ALTER TABLE import DROP project_id');
        $this->addSql('ALTER TABLE import CHANGE iso3 iso3 VARCHAR(3) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE import_project');
        $this->addSql('ALTER TABLE import ADD project_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE import ADD CONSTRAINT FK_9D4ECE1D166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_9D4ECE1D166D1F9C ON import (project_id)');
        $this->addSql('ALTER TABLE import DROP iso3');
    }
}
