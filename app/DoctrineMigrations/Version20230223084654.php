<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230223084654 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('RENAME TABLE smartcard_redemption_batch TO invoice');
        $this->addSql('ALTER TABLE smartcard_purchase DROP FOREIGN KEY FK_38CC60343A0A9AE7');
        $this->addSql('ALTER TABLE smartcard_purchase RENAME COLUMN redemption_batch_id TO invoice_id');
        $this->addSql('ALTER TABLE smartcard_purchase ADD CONSTRAINT FK_38CC60343A0A9AE7 FOREIGN KEY (invoice_id) REFERENCES invoice (id)');

        $this->addSql('DROP VIEW view_purchased_item');
        $this->addSql('DROP VIEW view_smartcard_preliminary_invoice');
        $this->addSql('DROP VIEW view_smartcard_purchased_item');

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
         LEFT JOIN smartcard_beneficiary s ON s.id=sd.smartcard_beneficiary_id
         LEFT JOIN smartcard_purchase sp ON s.id=sp.smartcard_beneficiary_id
         LEFT JOIN smartcard_purchase_record spr ON sp.id=spr.smartcard_purchase_id
         LEFT JOIN invoice sbatch ON sp.invoice_id=sbatch.id

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
            CREATE VIEW view_smartcard_preliminary_invoice AS
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
                               sp.smartcard_beneficiary_id  as smartcardId
                        FROM smartcard_purchase AS sp
                                 INNER JOIN smartcard_purchase_record AS spr ON sp.id = spr.smartcard_purchase_id
                        WHERE sp.invoice_id IS NULL
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
         LEFT JOIN smartcard_beneficiary s ON sp.smartcard_beneficiary_id = s.id
         LEFT JOIN invoice srb ON sp.invoice_id = srb.id
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
        $this->addSql('RENAME TABLE invoice TO smartcard_redemption_batch');
        $this->addSql('ALTER TABLE smartcard_purchase RENAME COLUMN invoice_id TO redemption_batch_id');
        $this->addSql('ALTER TABLE smartcard_purchase ADD CONSTRAINT FK_38CC60343A0A9AE7 FOREIGN KEY (redemption_batch_id) REFERENCES smartcard_redemption_batch (id)');
        $this->addSql('DROP VIEW view_purchased_item');
        $this->addSql('DROP VIEW view_smartcard_preliminary_invoice');
        $this->addSql('DROP VIEW view_smartcard_purchased_item');
    }
}
