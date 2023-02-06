<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221216201639 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE smartcard_deposit CHANGE hash hash VARCHAR(255) NOT NULL');

        $this->addSql('
            UPDATE smartcard_deposit sd
                INNER JOIN smartcard s on sd.smartcard_id = s.id
                INNER JOIN assistance_relief_package arp on sd.relief_package_id = arp.id
            SET sd.hash = MD5(CONCAT(s.code, "-", sd.value, "-", arp.unit, "-", arp.id));
        ');

        $this->addSql('CREATE UNIQUE INDEX unique_deposit_hash ON smartcard_deposit (hash)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            UPDATE smartcard_deposit sd
                INNER JOIN smartcard s on sd.smartcard_id = s.id
                INNER JOIN assistance_relief_package arp on sd.relief_package_id = arp.id
            SET sd.hash = MD5(CONCAT(s.code, "-", sd.distributed_at, "-", sd.value, "-", arp.unit, "-", arp.id));
        ');

        $this->addSql('DROP INDEX unique_deposit_hash ON smartcard_deposit');
    }
}
