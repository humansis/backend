<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180726134558 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE distribution_data DROP FOREIGN KEY FK_A54E7FD71376EC6E');
        $this->addSql('DROP INDEX IDX_A54E7FD71376EC6E ON distribution_data');
        $this->addSql('ALTER TABLE distribution_data DROP selection_criteria_id');
        $this->addSql('ALTER TABLE selection_criteria ADD distribution_data_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE selection_criteria ADD CONSTRAINT FK_61BAEEC9D744EF8E FOREIGN KEY (distribution_data_id) REFERENCES distribution_data (id)');
        $this->addSql('CREATE INDEX IDX_61BAEEC9D744EF8E ON selection_criteria (distribution_data_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE distribution_data ADD selection_criteria_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE distribution_data ADD CONSTRAINT FK_A54E7FD71376EC6E FOREIGN KEY (selection_criteria_id) REFERENCES selection_criteria (id)');
        $this->addSql('CREATE INDEX IDX_A54E7FD71376EC6E ON distribution_data (selection_criteria_id)');
        $this->addSql('ALTER TABLE selection_criteria DROP FOREIGN KEY FK_61BAEEC9D744EF8E');
        $this->addSql('DROP INDEX IDX_61BAEEC9D744EF8E ON selection_criteria');
        $this->addSql('ALTER TABLE selection_criteria DROP distribution_data_id');
    }
}
