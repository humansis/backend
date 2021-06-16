<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210616104527 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE import_beneficiary DROP FOREIGN KEY FK_FEC38F8AECCAAFA0');
        $this->addSql('ALTER TABLE import_beneficiary ADD CONSTRAINT FK_FEC38F8AECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES beneficiary (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE import_beneficiary DROP FOREIGN KEY FK_FEC38F8AECCAAFA0');
        $this->addSql('ALTER TABLE import_beneficiary ADD CONSTRAINT FK_FEC38F8AECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES beneficiary (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
