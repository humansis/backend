<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Enum\ModalityType;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220916142520 extends AbstractMigration
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

        $this->addSql('DROP VIEW view_distributed_item');
        $this->addSql($this->getViewSql());
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
        $modality = ModalityType::SMART_CARD;

        // update relief package amount spent after each modify in smartcard purchase record
        // expects only one smartcard relief package per assistance and beneficiary (= only one smartcard commodity per assistance)
        // expects beneficiary to use only one smartcard for purchases in assistance
        return <<<SQL
            CREATE TRIGGER `assistance_relief_package_amount_spent_trigger_${event}`
            AFTER ${event}
            ON `smartcard_purchase_record`
            FOR EACH ROW
                UPDATE `assistance_relief_package` arp
                SET arp.amount_spent = (
                    SELECT
                        sum(spr.value) AS total
                    FROM
                        smartcard_purchase sp
                    JOIN smartcard_purchase_record spr
                        ON sp.id = spr.smartcard_purchase_id
                        AND sp.assistance_id = (SELECT assistance_id FROM smartcard_purchase WHERE id = ${modifier}.smartcard_purchase_id)
                    JOIN smartcard s
                        ON sp.smartcard_id = s.id
                        AND s.id = (SELECT smartcard_id FROM smartcard_purchase WHERE id = ${modifier}.smartcard_purchase_id)
                )
                WHERE arp.assistance_beneficiary_id in (
                    SELECT db.id
                    FROM distribution_beneficiary db
                    JOIN (
                        SELECT
                            sp.assistance_id AS aid,
                            s.beneficiary_id AS bid
                        FROM
                            smartcard_purchase sp
                        JOIN smartcard_purchase_record spr
                            ON sp.id = spr.smartcard_purchase_id
                            AND sp.assistance_id = (SELECT assistance_id FROM smartcard_purchase WHERE id = ${modifier}.smartcard_purchase_id)
                        JOIN smartcard s
                            ON sp.smartcard_id = s.id
                            AND s.id = (SELECT smartcard_id FROM smartcard_purchase WHERE id = ${modifier}.smartcard_purchase_id)
                        GROUP by aid, bid
                    ) ps
                        ON db.assistance_id = ps.aid
                        AND db.beneficiary_id = ps.bid
                )
                AND arp.modality_type = '${modality}'
        SQL;
    }

    private function getUpdateSql(): string
    {
        $modality = ModalityType::SMART_CARD;

        // set amount spent for assistance_relief_package
        // expects only one smartcard relief package per assistance and beneficiary (= only one smartcard commodity per assistance)
        // expects beneficiary to use only one smartcard for purchases in assistance
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
                    AND arp.modality_type = '${modality}'

                -- filter only smartcard with joined beneficiaries
                JOIN smartcard_purchase sp
                    ON a.id = sp.assistance_id
                JOIN smartcard s
                    ON sp.smartcard_id = s.id
                    AND s.beneficiary_id = db.beneficiary_id

                JOIN smartcard_purchase_record spr
                    ON sp.id = spr.smartcard_purchase_id

                GROUP by arp.id
            ) t ON t.aprid = a.id
            SET a.amount_spent = t.total
        SQL;
    }

    private function getViewSql(): string
    {
        return <<<SQL
            CREATE VIEW view_distributed_item AS
                SELECT
                    pack.id,
                    db.beneficiary_id,
                    db.assistance_id,
                    a.project_id,
                    a.location_id,
                    CASE
                        WHEN ab.bnf_type = "hh"   THEN "Household"
                        WHEN ab.bnf_type = "bnf"  THEN "Beneficiary"
                        WHEN ab.bnf_type = "inst" THEN "Institution"
                        WHEN ab.bnf_type = "comm" THEN "Community"
                        END AS bnf_type,
                    c.id as commodity_id,
                    pack.modality_type as modality_type,
                    pack.distributedAt AS date_distribution,
                    CASE
                        WHEN sd.id IS NOT NULL THEN s.code
                        WHEN b.id  IS NOT NULL THEN b.code
                        END AS carrier_number,
                    CASE
                        WHEN sd.id  IS NOT NULL THEN sd.value
                        WHEN t.id   IS NOT NULL THEN CAST(REGEXP_SUBSTR(t.amount_sent, "[0-9]+(\.[0-9]+)?") AS DECIMAL)
                        WHEN b.id   IS NOT NULL THEN b.value
                        WHEN pack.amount_distributed > 0 THEN pack.amount_distributed
                        END AS amount,
                    pack.amount_spent as spent,
                    CASE
                        WHEN sd.distributed_by_id IS NOT NULL THEN sd.distributed_by_id
                        WHEN t.id  IS NOT NULL THEN t.sent_by_id
                        ELSE pack.distributed_by_id
                        END AS field_officer_id

                FROM distribution_beneficiary db
                         JOIN assistance_relief_package pack ON pack.assistance_beneficiary_id=db.id AND pack.amount_distributed > 0
                         JOIN assistance a ON a.id=db.assistance_id AND a.assistance_type="distribution"
                         JOIN abstract_beneficiary ab ON ab.id=db.beneficiary_id
                         JOIN commodity c ON c.assistance_id=db.assistance_id

                    -- smartcards
                         LEFT JOIN smartcard_deposit sd ON sd.relief_package_id=pack.id
                         LEFT JOIN smartcard s ON s.id=sd.smartcard_id

                    -- mobile money
                         LEFT JOIN transaction t ON t.relief_package_id=pack.id and t.transaction_status=1

                    -- booklets
                         LEFT JOIN (
                            SELECT
                                b.id,
                                b.code,
                                b.relief_package_id,
                                MAX(vp.used_at) AS used_at,
                                SUM(vpr.value) AS value
                            FROM booklet b
                                     JOIN voucher v ON v.booklet_id=b.id
                                     LEFT OUTER JOIN voucher_purchase vp ON vp.id=v.voucher_purchase_id
                                     LEFT OUTER JOIN voucher_purchase_record vpr ON vpr.voucher_purchase_id=vp.id
                            WHERE b.relief_package_id IS NOT NULL
                            GROUP BY b.id, b.code, b.relief_package_id
                ) AS b ON b.relief_package_id=pack.id;
        SQL;
    }
}
