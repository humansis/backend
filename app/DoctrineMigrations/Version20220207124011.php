<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220207124011 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE import_beneficiary_duplicity ADD household_duplicity_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE import_beneficiary_duplicity ADD CONSTRAINT FK_CD6A7AF7EF23601F FOREIGN KEY (household_duplicity_id) REFERENCES import_household_duplicity (id)');
        $this->addSql('CREATE INDEX IDX_CD6A7AF7EF23601F ON import_beneficiary_duplicity (household_duplicity_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE import_beneficiary_duplicity DROP FOREIGN KEY FK_CD6A7AF7EF23601F');
        $this->addSql('DROP INDEX IDX_CD6A7AF7EF23601F ON import_beneficiary_duplicity');
        $this->addSql('ALTER TABLE import_beneficiary_duplicity DROP household_duplicity_id');
        $this->addSql('ALTER TABLE import_household_duplicity CHANGE decide_at decide_at DATETIME DEFAULT NULL');
    }
}
