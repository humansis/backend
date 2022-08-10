<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220810142924 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE household ADD iso3 VARCHAR(3)');
        $this->addSql('UPDATE `household` hh INNER JOIN `abstract_beneficiary` ab ON ab.id = hh.id INNER JOIN `abstract_beneficiary_project` abp ON ab.id = abp.abstract_beneficiary_id INNER JOIN `project` p ON p.id = abp.project_id SET hh.iso3 = p.iso3');
        $this->addSql('ALTER TABLE household CHANGE iso3 iso3 VARCHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE scoring_blueprint CHANGE iso3 iso3 VARCHAR(3) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE household DROP iso3');
    }
}
