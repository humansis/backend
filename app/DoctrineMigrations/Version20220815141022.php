<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220815141022 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE assistance_relief_package ADD amount_spent NUMERIC(10, 2) DEFAULT NULL AFTER amount_distributed');
        $this->addSql($this->getUpdateSql());
        $this->addSql($this->getTriggerSql('INSERT'));
        $this->addSql($this->getTriggerSql('UPDATE'));
        $this->addSql($this->getTriggerSql('DELETE'));
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TRIGGER IF EXISTS assistance_relief_package_amount_spent_trigger_delete');
        $this->addSql('DROP TRIGGER IF EXISTS assistance_relief_package_amount_spent_trigger_update');
        $this->addSql('DROP TRIGGER IF EXISTS assistance_relief_package_amount_spent_trigger_insert');
        $this->addSql('ALTER TABLE assistance_relief_package DROP amount_spent');
    }

    private function getTriggerSql($event): string
    {
        $modifier = $event === 'DELETE' ? 'OLD' : 'NEW';
        $updateSql =  $this->getUpdateSql($modifier);

        return <<<SQL
            CREATE TRIGGER `assistance_relief_package_amount_spent_trigger_${event}`
            AFTER ${event}
            ON `smartcard_purchase_record`
            FOR EACH ROW
            ${updateSql}
        SQL;
    }

    private function getUpdateSql($modifier = null): string
    {
        $reduceSql = $modifier === null
            ? ''
            : "AND sp.id = ${modifier}.smartcard_purchase_id";

        return <<<SQL
            UPDATE `assistance_relief_package` a
            JOIN (
                SELECT
                    arp.id AS aprid,
                    sum(spr.value) AS total
                FROM assistance a

                -- get beneficiaries
                JOIN distribution_beneficiary db
                    ON a.id = db.assistance_id
                JOIN assistance_relief_package arp
                    ON db.id = arp.assistance_beneficiary_id

                -- filter only smartcard with joined beneficiaries
                JOIN smartcard_purchase sp
                    ON a.id = sp.assistance_id
                JOIN smartcard s
                    ON sp.smartcard_id = s.id
                    AND s.beneficiary_id = db.beneficiary_id

                JOIN smartcard_purchase_record spr
                    ON sp.id = spr.smartcard_purchase_id
                    ${reduceSql}

                GROUP by arp.id
            ) t ON t.aprid = a.id
            SET a.amount_spent = t.total
        SQL;
    }
}
