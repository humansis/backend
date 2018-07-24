<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180723135239 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE distribution_beneficiary DROP FOREIGN KEY FK_EA141F3057D9B02');
        $this->addSql('DROP TABLE project_beneficiary');
        $this->addSql('DROP INDEX IDX_EA141F3057D9B02 ON distribution_beneficiary');
        $this->addSql('ALTER TABLE distribution_beneficiary CHANGE project_beneficiary_id beneficiary_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE distribution_beneficiary ADD CONSTRAINT FK_EA141F30ECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES beneficiary (id)');
        $this->addSql('CREATE INDEX IDX_EA141F30ECCAAFA0 ON distribution_beneficiary (beneficiary_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE project_beneficiary (id INT AUTO_INCREMENT NOT NULL, beneficiary_id INT DEFAULT NULL, project_id INT DEFAULT NULL, INDEX IDX_B270B391ECCAAFA0 (beneficiary_id), INDEX IDX_B270B391166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_beneficiary ADD CONSTRAINT FK_B270B391166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_beneficiary ADD CONSTRAINT FK_B270B391ECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES beneficiary (id)');
        $this->addSql('ALTER TABLE distribution_beneficiary DROP FOREIGN KEY FK_EA141F30ECCAAFA0');
        $this->addSql('DROP INDEX IDX_EA141F30ECCAAFA0 ON distribution_beneficiary');
        $this->addSql('ALTER TABLE distribution_beneficiary CHANGE beneficiary_id project_beneficiary_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE distribution_beneficiary ADD CONSTRAINT FK_EA141F3057D9B02 FOREIGN KEY (project_beneficiary_id) REFERENCES project_beneficiary (id)');
        $this->addSql('CREATE INDEX IDX_EA141F3057D9B02 ON distribution_beneficiary (project_beneficiary_id)');
    }
}
