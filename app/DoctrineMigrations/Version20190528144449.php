<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190528144449 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE household DROP FOREIGN KEY FK_54C32FC0A10695A9');
        $this->addSql('DROP INDEX UNIQ_54C32FC0A10695A9 ON household');
        $this->addSql('ALTER TABLE household DROP household_location_id');
        $this->addSql('ALTER TABLE household_location ADD household_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE household_location ADD CONSTRAINT FK_822570EEE79FF843 FOREIGN KEY (household_id) REFERENCES household (id)');
        $this->addSql('CREATE INDEX IDX_822570EEE79FF843 ON household_location (household_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE household ADD household_location_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE household ADD CONSTRAINT FK_54C32FC0A10695A9 FOREIGN KEY (household_location_id) REFERENCES household_location (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_54C32FC0A10695A9 ON household (household_location_id)');
        $this->addSql('ALTER TABLE household_location DROP FOREIGN KEY FK_822570EEE79FF843');
        $this->addSql('DROP INDEX IDX_822570EEE79FF843 ON household_location');
        $this->addSql('ALTER TABLE household_location DROP household_id');
    }
}
