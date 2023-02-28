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
        // clean doubled deposits
        $this->addSql('DROP TEMPORARY TABLE IF EXISTS doubled_smartcard_deposit;');
        $this->addSql("CREATE TEMPORARY TABLE doubled_smartcard_deposit
            SELECT sd1.id
            FROM smartcard_deposit sd1
                     INNER JOIN (SELECT JSON_REMOVE(JSON_ARRAYAGG(sd.id), '$[0]') as idsToRemove
                                 FROM assistance_relief_package arp
                                          INNER JOIN smartcard_deposit sd on arp.id = sd.relief_package_id
                                 GROUP BY arp.id, sd.smartcard_id, sd.value
                                 HAVING count(arp.id) > 1) sub ON JSON_CONTAINS(sub.idsToRemove, CAST(sd1.id as json), '$');
        ");
        $this->addSql('DELETE FROM smartcard_deposit WHERE id IN (SELECT id FROM doubled_smartcard_deposit);');

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
