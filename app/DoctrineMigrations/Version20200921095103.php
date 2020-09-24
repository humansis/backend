<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200921095103 extends AbstractMigration
{
    public function getDescription()
    {
        return 'Migrate all default modalities to DB';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE UNIQUE INDEX temporary_uq_idx ON modality (name)');
        $this->addSql('INSERT IGNORE INTO modality (name) VALUES ("Cash"), ("Voucher"), ("In Kind"), ("Other")');
        $this->addSql('DROP INDEX temporary_uq_idx ON modality');

        $this->addSql('CREATE UNIQUE INDEX temporary_uq2_idx ON modality_type (name)');
        $this->addSql('INSERT IGNORE INTO modality_type (modality_id, name) SELECT id, "Mobile Money" FROM modality WHERE name="Cash"');
        $this->addSql('INSERT IGNORE INTO modality_type (modality_id, name) SELECT id, "Cash" FROM modality WHERE name="Cash"');
        $this->addSql('INSERT IGNORE INTO modality_type (modality_id, name) SELECT id, "Smartcard" FROM modality WHERE name="Cash"');
        $this->addSql('INSERT IGNORE INTO modality_type (modality_id, name) SELECT id, "Cash/Bank transfer" FROM modality WHERE name="Cash"');
        $this->addSql('INSERT IGNORE INTO modality_type (modality_id, name) SELECT id, "QR Code Voucher" FROM modality WHERE name="Voucher"');
        $this->addSql('INSERT IGNORE INTO modality_type (modality_id, name) SELECT id, "Paper Voucher" FROM modality WHERE name="Voucher"');
        $this->addSql('INSERT IGNORE INTO modality_type (modality_id, name) SELECT id, "Food" FROM modality WHERE name="In Kind"');
        $this->addSql('INSERT IGNORE INTO modality_type (modality_id, name) SELECT id, "RTE Kit" FROM modality WHERE name="In Kind"');
        $this->addSql('INSERT IGNORE INTO modality_type (modality_id, name) SELECT id, "Bread" FROM modality WHERE name="In Kind"');
        $this->addSql('INSERT IGNORE INTO modality_type (modality_id, name) SELECT id, "Agricultural Kit" FROM modality WHERE name="In Kind"');
        $this->addSql('INSERT IGNORE INTO modality_type (modality_id, name) SELECT id, "WASH Kit" FROM modality WHERE name="In Kind"');
        $this->addSql('INSERT IGNORE INTO modality_type (modality_id, name) SELECT id, "Shelter tool kit" FROM modality WHERE name="In Kind"');
        $this->addSql('INSERT IGNORE INTO modality_type (modality_id, name) SELECT id, "Hygiene kit" FROM modality WHERE name="In Kind"');
        $this->addSql('INSERT IGNORE INTO modality_type (modality_id, name) SELECT id, "Dignity kit" FROM modality WHERE name="In Kind"');
        $this->addSql('INSERT IGNORE INTO modality_type (modality_id, name) SELECT id, "Other NFI" FROM modality WHERE name="In Kind"');
        $this->addSql('INSERT IGNORE INTO modality_type (modality_id, name) SELECT id, "Loan" FROM modality WHERE name="Other"');
        $this->addSql('INSERT IGNORE INTO modality_type (modality_id, name) SELECT id, "Business Grant" FROM modality WHERE name="Other"');
        $this->addSql('DROP INDEX temporary_uq2_idx ON modality_type');
    }

    public function down(Schema $schema): void
    {
    }
}
