<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230105085652 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE smartcard RENAME TO smartcard_beneficiary");
        $this->addSql("ALTER TABLE smartcard_audit RENAME TO smartcard_beneficiary_audit");

        $this->addSql('ALTER TABLE smartcard_deposit DROP FOREIGN KEY FK_FD578545AC8B107D, RENAME COLUMN smartcard_id TO smartcard_beneficiary_id');
        $this->addSql('ALTER TABLE smartcard_purchase DROP FOREIGN KEY FK_38CC6034AC8B107D, RENAME COLUMN smartcard_id TO smartcard_beneficiary_id');

        $this->addSql('ALTER TABLE smartcard_deposit ADD CONSTRAINT FK_FD578545AC8B107D FOREIGN KEY (smartcard_beneficiary_id) REFERENCES `smartcard_beneficiary` (id)');
        $this->addSql('ALTER TABLE smartcard_purchase ADD CONSTRAINT FK_38CC6034AC8B107D FOREIGN KEY (smartcard_beneficiary_id) REFERENCES `smartcard_beneficiary` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE smartcard_beneficiary RENAME TO smartcard");
        $this->addSql("ALTER TABLE smartcard_beneficiary_audit RENAME TO smartcard_audit");

        $this->addSql('ALTER TABLE smartcard_deposit RENAME COLUMN smartcard_beneficiary_id TO smartcard_id, ADD CONSTRAINT FK_FD578545AC8B107D FOREIGN KEY (smartcard_id) REFERENCES `smartcard` (id)');
        $this->addSql('ALTER TABLE smartcard_purchase RENAME COLUMN smartcard_beneficiary_id TO smartcard_id, ADD CONSTRAINT FK_38CC6034AC8B107D FOREIGN KEY (smartcard_id) REFERENCES `smartcard` (id)');
    }
}
