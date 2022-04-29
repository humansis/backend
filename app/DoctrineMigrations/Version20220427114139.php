<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220427114139 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // update views: view_assistance_statistics, view_distributed_item, view_purchased_item, view_smartcard_purchased_item
        $this->addSql('DROP VIEW view_assistance_statistics');
        $this->addSql('DROP VIEW view_distributed_item');
        $this->addSql('DROP VIEW view_purchased_item');
        $this->addSql('DROP VIEW view_smartcard_purchased_item');
        $this->addSql('CREATE VIEW view_assistance_statistics AS
SELECT
    assistance_id,
    COUNT(beneficiary)                             AS number_of_beneficiaries,
    CAST(SUM(amountTotal) AS decimal(15, 2))       AS amount_total,
    CAST(SUM(amountDistributed) AS decimal(15, 2)) AS amount_distributed,
    CAST(SUM(amountUsed) AS decimal(15, 2))        AS amount_used,
    CAST(SUM(amountSent) AS decimal(15, 2))        AS amount_sent,
    CAST(SUM(amountPickedUp) AS decimal(15, 2))    AS amount_picked_up
FROM (
         SELECT
             a.id as assistance_id,

             CASE WHEN db.removed=0 THEN 1 END AS beneficiary,

             CASE WHEN db.removed=0 THEN c.value END AS amountTotal,

             CASE
                 WHEN db.removed=0 AND pack.id IS NOT NULL                                  THEN pack.amount_distributed
                 WHEN db.removed=0 AND gri.id IS NOT NULL AND gri.distributedAt IS NOT NULL THEN c.value
                 WHEN db.removed=0 AND t.id IS NOT NULL AND t.amount_sent IS NOT NULL       THEN CAST(SUBSTRING_INDEX(t.amount_sent, " ", -1) AS decimal(10, 2))
                 WHEN db.removed=0 AND b.id IS NOT NULL                                     THEN b.value
                 END AS amountDistributed,

             CASE
                 WHEN db.removed=0 AND sd.id IS NOT NULL                                    THEN sd.value
                 WHEN db.removed=0 AND gri.id IS NOT NULL AND gri.distributedAt IS NOT NULL THEN c.value
                 WHEN db.removed=0 AND t.id IS NOT NULL AND t.money_received = 1            THEN CAST(SUBSTRING_INDEX(t.amount_sent, " ", -1) AS decimal(10, 2))
                 WHEN db.removed=0 AND b.id IS NOT NULL                                     THEN b.value
                 END AS amountUsed,

             CASE
                 WHEN db.removed=0 AND sd.id IS NOT NULL                                    THEN sd.value
                 WHEN db.removed=0 AND gri.id IS NOT NULL AND gri.distributedAt IS NOT NULL THEN c.value
                 WHEN db.removed=0 AND t.id IS NOT NULL AND t.date_sent IS NOT NULL         THEN CAST(SUBSTRING_INDEX(t.amount_sent, " ", -1) AS decimal(10, 2))
                 WHEN db.removed=0 AND b.id IS NOT NULL                                     THEN b.value
                 END AS amountSent,

             CASE
                 WHEN db.removed=0 AND sd.id IS NOT NULL                                    THEN sd.value
                 WHEN db.removed=0 AND gri.id IS NOT NULL AND gri.distributedAt IS NOT NULL THEN c.value
                 WHEN db.removed=0 AND t.id IS NOT NULL AND t.pickup_date IS NOT NULL       THEN CAST(SUBSTRING_INDEX(t.amount_sent, " ", -1) AS decimal(10, 2))
                 WHEN db.removed=0 AND b.id IS NOT NULL                                     THEN b.value
                 END AS amountPickedUp

         FROM assistance a
                  LEFT JOIN distribution_beneficiary db on a.id = db.assistance_id
                  LEFT JOIN commodity c ON db.assistance_id=c.assistance_id
             -- smartcards
                  LEFT JOIN assistance_relief_package pack ON pack.assistance_beneficiary_id=db.id
                  LEFT JOIN smartcard_deposit sd ON sd.relief_package_id=pack.id
             -- mobile money
                  LEFT JOIN transaction t ON t.distribution_beneficiary_id=db.id AND t.transaction_status=1
             -- general reliefs
                  LEFT JOIN general_relief_item gri ON gri.distribution_beneficiary_id=db.id
             -- booklets
                  LEFT JOIN (
             SELECT
                 b.id,
                 b.distribution_beneficiary_id,
                 SUM(vpr.value) AS value
             FROM booklet b
                      JOIN voucher v ON v.booklet_id=b.id
                      JOIN voucher_purchase vp ON vp.id=v.voucher_purchase_id
                      JOIN voucher_purchase_record vpr ON vpr.voucher_purchase_id=vp.id
             WHERE b.distribution_beneficiary_id IS NOT NULL
             GROUP BY b.id, b.distribution_beneficiary_id
         ) AS b ON b.distribution_beneficiary_id=db.id
     ) AS counts GROUP BY assistance_id');
        $this->addSql('CREATE VIEW view_distributed_item AS
SELECT
    CASE
        WHEN sd.id  IS NOT NULL THEN CONCAT(db.id, "_", sd.id)
        WHEN t.id   IS NOT NULL THEN CONCAT(db.id, "_", t.id)
        WHEN gri.id IS NOT NULL THEN CONCAT(db.id, "_", gri.id)
        WHEN b.id   IS NOT NULL THEN CONCAT(db.id, "_", b.id)
        END AS id,
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
    mt.name as modality_type,
    CASE
        WHEN sd.id  IS NOT NULL THEN DATE_FORMAT(sd.distributed_at, "%Y-%m-%dT%TZ")
        WHEN t.id   IS NOT NULL THEN DATE_FORMAT(t.pickup_date, "%Y-%m-%dT%TZ")
        WHEN gri.id IS NOT NULL THEN DATE_FORMAT(gri.distributedAt, "%Y-%m-%dT%TZ")
        WHEN b.id   IS NOT NULL THEN DATE_FORMAT(b.used_at, "%Y-%m-%dT%TZ")
        END AS date_distribution,
    CASE
        WHEN sd.id IS NOT NULL THEN s.code
        WHEN b.id  IS NOT NULL THEN b.code
        END AS carrier_number,
    CASE
        WHEN sd.id  IS NOT NULL THEN sd.value
        WHEN t.id   IS NOT NULL THEN CAST(REGEXP_SUBSTR(t.amount_sent, "[0-9]+(\.[0-9]+)?") AS DECIMAL)
        WHEN gri.id IS NOT NULL THEN c.value
        WHEN b.id   IS NOT NULL THEN b.value
        END AS amount,
    CASE
        WHEN sd.distributed_by_id IS NOT NULL THEN sd.distributed_by_id
        WHEN t.id  IS NOT NULL THEN t.sent_by_id
        END AS field_officer_id

FROM distribution_beneficiary db
         JOIN assistance a ON a.id=db.assistance_id AND a.assistance_type="distribution"
         JOIN abstract_beneficiary ab ON ab.id=db.beneficiary_id
         JOIN commodity c ON c.assistance_id=db.assistance_id
         JOIN modality_type mt ON mt.id=c.modality_type_id

    -- smartcards
         LEFT JOIN assistance_relief_package pack ON pack.assistance_beneficiary_id=db.id
         LEFT JOIN smartcard_deposit sd ON sd.relief_package_id=pack.id
         LEFT JOIN smartcard s ON s.id=sd.smartcard_id

    -- mobile money
         LEFT JOIN transaction t ON t.distribution_beneficiary_id=db.id and t.transaction_status=1

    -- general reliefs
         LEFT JOIN general_relief_item gri ON gri.distribution_beneficiary_id=db.id AND gri.distributedAt IS NOT NULL

    -- booklets
         LEFT JOIN (
    SELECT
        b.id,
        b.code,
        b.distribution_beneficiary_id,
        MAX(vp.used_at) AS used_at,
        SUM(vpr.value) AS value
    FROM booklet b
             JOIN voucher v ON v.booklet_id=b.id
             LEFT OUTER JOIN voucher_purchase vp ON vp.id=v.voucher_purchase_id
             LEFT OUTER JOIN voucher_purchase_record vpr ON vpr.voucher_purchase_id=vp.id
    WHERE b.distribution_beneficiary_id IS NOT NULL
    GROUP BY b.id, b.code, b.distribution_beneficiary_id
) AS b ON b.distribution_beneficiary_id=db.id

WHERE (sd.id IS NOT NULL OR gri.id IS NOT NULL OR t.id IS NOT NULL OR b.id IS NOT NULL)
  AND (sd.distributed_at IS NOT NULL OR t.pickup_date IS NOT NULL OR gri.distributedAt IS NOT NULL OR b.id)
');
        $this->addSql('CREATE VIEW view_purchased_item AS
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
    mt.name as modality_type,
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
         JOIN modality_type mt ON mt.id=c.modality_type_id

    -- smartcards
         LEFT JOIN assistance_relief_package pack ON pack.assistance_beneficiary_id=db.id
         LEFT JOIN smartcard_deposit sd ON sd.relief_package_id=pack.id
         LEFT JOIN smartcard s ON s.id=sd.smartcard_id
         LEFT JOIN smartcard_purchase sp ON s.id=sp.smartcard_id
         LEFT JOIN smartcard_purchase_record spr ON sp.id=spr.smartcard_purchase_id
         LEFT JOIN smartcard_redemption_batch sbatch ON sp.redemption_batch_id=sbatch.id

    -- vouchers
         LEFT JOIN booklet b ON b.distribution_beneficiary_id=db.id
         LEFT JOIN voucher v ON v.booklet_id=b.id
         LEFT JOIN voucher_purchase vp ON vp.id=v.voucher_purchase_id
         LEFT JOIN voucher_purchase_record vpr ON vpr.voucher_purchase_id=vp.id

WHERE (spr.id IS NOT NULL OR vpr.id IS NOT NULL)
');
        $this->addSql('CREATE VIEW view_smartcard_purchased_item AS
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
         LEFT JOIN smartcard s ON sp.smartcard_id = s.id
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
');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(true, 'Cannot be downgraded');
    }
}
