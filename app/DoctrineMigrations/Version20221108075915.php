<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221108075915 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
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
                           INNER JOIN smartcard s ON spPre.smartcardId = s.id
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
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            <<<SQL
            CREATE OR REPLACE VIEW view_smartcard_preliminary_invoice AS
            SELECT IF(
                       a.project_id IS NOT NULL,
                       CONCAT(spa.vendor_id, "_", spa.currency, "_", a.project_id),
                       CONCAT(spa.vendor_id, "_", spa.currency, "_", "NULL")
                       )                    AS id,
                   a.project_id             as project_id,
                   spa.currency             as currency,
                   spa.vendor_id            as vendor_id,
                   SUM(spa.value)           as value,
                   JSON_ARRAYAGG(spa.spaid) as purchase_ids,
                   COUNT(spa.spaid)         as purchase_count
            FROM (
                SELECT sp.id            as spaid,
                         sp.assistance_id as sp_ass,
                         SUM(spr.value)   as value,
                         spr.currency     as currency,
                         sp.vendor_id     as vendor_id

                FROM smartcard_purchase AS sp
                INNER JOIN smartcard_purchase_record AS spr ON sp.id = spr.smartcard_purchase_id
                  WHERE sp.redemption_batch_id IS NULL
                    AND vendor_id IS NOT NULL
                    AND currency IS NOT NULL
                  GROUP BY spr.currency, sp.id
            ) spa
                LEFT JOIN assistance a on spa.sp_ass = a.id
            GROUP BY spa.currency, a.project_id, spa.vendor_id
            ORDER BY spa.currency, a.project_id, spa.vendor_id;
        SQL
        );
    }
}
