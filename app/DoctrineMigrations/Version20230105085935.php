<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Enum\ModalityType;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230105085935 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TRIGGER IF EXISTS assistance_relief_package_amount_spent_trigger_delete');
        $this->addSql('DROP TRIGGER IF EXISTS assistance_relief_package_amount_spent_trigger_update');
        $this->addSql('DROP TRIGGER IF EXISTS assistance_relief_package_amount_spent_trigger_insert');

        $this->addSql($this->getTriggerSql('INSERT'));
        $this->addSql($this->getTriggerSql('UPDATE'));
        $this->addSql($this->getTriggerSql('DELETE'));

        $this->addSql('DROP VIEW view_distributed_item');
        $this->addSql('DROP VIEW view_purchased_item');
        $this->addSql('DROP VIEW view_smartcard_preliminary_invoice');
        $this->addSql('DROP VIEW view_smartcard_purchased_item');

        $this->addSql($this->getViewSql());
        $this->addSql(
            'CREATE VIEW view_purchased_item AS
        SELECT
        CASE
        WHEN sd.id  IS NOT NULL THEN CONCAT(db.id, "_", sd.id, "_", spr.product_id)
        WHEN b.id   IS NOT NULL THEN CONCAT(db.id, "_", b.id, "_", vpr.product_id)
        END AS id,
        db.beneficiary_id,
        db.assistance_id,
        a.project_id,
        a.location_id,
        CASE
        WHEN sd.id  IS NOT NULL THEN spr.product_id
        WHEN b.id   IS NOT NULL THEN vpr.product_id
        END AS product_id,
        CASE
        WHEN ab.bnf_type = "hh"   THEN "Household"
        WHEN ab.bnf_type = "bnf"  THEN "Beneficiary"
        WHEN ab.bnf_type = "inst" THEN "Institution"
        WHEN ab.bnf_type = "comm" THEN "Community"
        END AS bnf_type,
        c.id as commodity_id,
        c.modality_type as modality_type,
        CASE
        WHEN sd.id  IS NOT NULL THEN DATE_FORMAT(sd.distributed_at, "%Y-%m-%dT%TZ")
        WHEN b.id   IS NOT NULL THEN null
        END AS date_distribution,
        CASE
        WHEN sd.id  IS NOT NULL THEN DATE_FORMAT(sp.used_at, "%Y-%m-%dT%TZ")
        WHEN b.id   IS NOT NULL THEN DATE_FORMAT(vp.used_at, "%Y-%m-%dT%TZ")
        END AS date_purchase,
        CASE
        WHEN sd.id IS NOT NULL THEN s.code
        WHEN b.id  IS NOT NULL THEN v.code
        END AS carrier_number,
        CASE
        WHEN sd.id IS NOT NULL THEN spr.value
        WHEN b.id  IS NOT NULL THEN vpr.value
        END AS value,
        CASE
        WHEN sd.id  IS NOT NULL THEN sp.vendor_id
        WHEN b.id   IS NOT NULL THEN vp.vendor_id
        END AS vendor_id,
        CASE
        WHEN sd.id  IS NOT NULL THEN LPAD(sbatch.id, 6, 0)
        WHEN b.id   IS NOT NULL THEN null
        END AS invoice_number,
        CASE
        WHEN sd.id  IS NOT NULL THEN spr.currency
        WHEN b.id   IS NOT NULL THEN b.currency
        END AS currency

        FROM distribution_beneficiary db
         JOIN assistance a ON a.id=db.assistance_id AND a.assistance_type="distribution"
         JOIN abstract_beneficiary ab ON ab.id=db.beneficiary_id
         JOIN commodity c ON c.assistance_id=db.assistance_id

        -- smartcards
         LEFT JOIN assistance_relief_package pack ON pack.assistance_beneficiary_id=db.id
         LEFT JOIN smartcard_deposit sd ON sd.relief_package_id=pack.id
         LEFT JOIN smartcard_beneficiary s ON s.id=sd.smartcard_id
         LEFT JOIN smartcard_purchase sp ON s.id=sp.smartcard_id
         LEFT JOIN smartcard_purchase_record spr ON sp.id=spr.smartcard_purchase_id
         LEFT JOIN smartcard_redemption_batch sbatch ON sp.redemption_batch_id=sbatch.id

        -- vouchers
         LEFT JOIN booklet b ON b.distribution_beneficiary_id=db.id
         LEFT JOIN voucher v ON v.booklet_id=b.id
         LEFT JOIN voucher_purchase vp ON vp.id=v.voucher_purchase_id
         LEFT JOIN voucher_purchase_record vpr ON vpr.voucher_purchase_id=vp.id

        WHERE (spr.id IS NOT NULL OR vpr.id IS NOT NULL)
        '
        );
        $this->addSql(
            <<<SQL
            CREATE OR REPLACE VIEW view_smartcard_preliminary_invoice AS
            SELECT IF(
                       a.project_id IS NOT NULL,
                       CONCAT(spa.vendor_id, "_", spa.currency, "_", a.project_id),
                       CONCAT(spa.vendor_id, "_", spa.currency, "_", "NULL")
                       )                                    AS id,
                   a.project_id                             as project_id,
                   spa.currency                             as currency,
                   spa.vendor_id                            as vendor_id,
                   SUM(spa.value)                           as value,
                   JSON_ARRAYAGG(spa.spaid)                 as purchase_ids,
                   count(spa.spaid)                         as purchase_count,
                   IF(MIN(spa.is_redeemable) = 0, FALSE, TRUE) as is_redeemable
            FROM (SELECT spPre.spaid                                            as spaid,
                         spPre.sp_ass                                           as sp_ass,
                         spPre.value                                            as value,
                         spPre.currency                                         as currency,
                         spPre.vendor_id                                        as vendor_id,
                         IF(SUM(IF(arp.state = 'To distribute', 1, 0)) = 0 AND
                            SUM(IF(arp.state = 'Distributed', 1, 0)) > 0, 1, 0) as is_redeemable
                  FROM (SELECT sp.id            as spaid,
                               sp.assistance_id as sp_ass,
                               SUM(spr.value)   as value,
                               spr.currency     as currency,
                               sp.vendor_id     as vendor_id,
                               sp.smartcard_id  as smartcardId
                        FROM smartcard_purchase AS sp
                                 INNER JOIN smartcard_purchase_record AS spr ON sp.id = spr.smartcard_purchase_id
                        WHERE sp.redemption_batch_id IS NULL
                          AND sp.vendor_id IS NOT NULL
                          AND spr.currency IS NOT NULL
                        GROUP BY spr.currency, sp.id) spPre
                           INNER JOIN smartcard_beneficiary s ON spPre.smartcardId = s.id
                           LEFT JOIN abstract_beneficiary ab ON s.beneficiary_id = ab.id
                           LEFT JOIN distribution_beneficiary db
                                      ON db.beneficiary_id = ab.id AND db.assistance_id = spPre.sp_ass
                           LEFT JOIN assistance_relief_package arp ON db.id = arp.assistance_beneficiary_id
                  GROUP BY spPre.spaid, spPre.currency) spa
                     LEFT JOIN assistance a ON spa.sp_ass = a.id
            GROUP BY currency, project_id, vendor_id
            ORDER BY currency, project_id, vendor_id
            SQL
        );
        $this->addSql(
            'CREATE VIEW view_smartcard_purchased_item AS
        SELECT
        spr.id,
        s.beneficiary_id as beneficiary_id,
        b.household_id as household_id,
        a.id as assistance_id,
        a.project_id,
        a.location_id,
        spr.product_id,
        DATE_FORMAT(sp.used_at, "%Y-%m-%dT%TZ") AS date_purchase,
        s.code as smartcard_code,
        spr.value,
        sp.vendor_id,
        LPAD(srb.id, 6, 0) AS invoice_number,
        spr.currency,
        ni.id_number
        FROM smartcard_purchase_record spr
         LEFT JOIN smartcard_purchase sp ON sp.id = spr.smartcard_purchase_id
         LEFT JOIN smartcard_beneficiary s ON sp.smartcard_id = s.id
         LEFT JOIN smartcard_redemption_batch srb ON sp.redemption_batch_id = srb.id
         LEFT JOIN beneficiary b ON s.beneficiary_id = b.id
         LEFT JOIN person p ON b.person_id = p.id
         LEFT JOIN national_id ni ON ni.id = ( -- to ensure that only 1 (first one) national id will be joined and no duplicities occur
                SELECT national_id.id
                FROM national_id
                WHERE national_id.person_id = p.id
                LIMIT 1
            )
         LEFT JOIN distribution_beneficiary db ON db.assistance_id = sp.assistance_id AND db.beneficiary_id = b.id
         LEFT JOIN assistance_relief_package rp ON rp.id = (
                SELECT reliefPackage.id
                FROM assistance_relief_package reliefPackage
                WHERE reliefPackage.assistance_beneficiary_id = db.id
                LIMIT 1
            )
         LEFT JOIN assistance a ON db.assistance_id = a.id
        '
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TRIGGER IF EXISTS assistance_relief_package_amount_spent_trigger_delete');
        $this->addSql('DROP TRIGGER IF EXISTS assistance_relief_package_amount_spent_trigger_update');
        $this->addSql('DROP TRIGGER IF EXISTS assistance_relief_package_amount_spent_trigger_insert');

        $this->addSql('DROP VIEW view_distributed_item');
        $this->addSql('DROP VIEW view_purchased_item');
        $this->addSql('DROP VIEW view_smartcard_preliminary_invoice');
        $this->addSql('DROP VIEW view_smartcard_purchased_item');
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
                    JOIN smartcard_beneficiary s
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
                        JOIN smartcard_beneficiary s
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
                         LEFT JOIN smartcard_beneficiary s ON s.id=sd.smartcard_id

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
