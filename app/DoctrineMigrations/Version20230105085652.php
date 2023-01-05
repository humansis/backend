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
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE smartcard_beneficiary RENAME TO smartcard");
        $this->addSql("ALTER TABLE smartcard_beneficiary_audit RENAME TO smartcard_audit");
    }
}
