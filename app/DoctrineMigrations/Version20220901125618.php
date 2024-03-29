<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220901125618 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

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

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            <<<SQL
            CREATE OR REPLACE VIEW view_smartcard_preliminary_invoice AS
                SELECT IF(a.project_id IS NOT NULL, CONCAT(sp.vendor_id, "_", spr.currency, "_", a.project_id),
                          CONCAT(sp.vendor_id, "_", spr.currency, "_", "NULL")) AS id,
                       a.project_id                                           as project_id,
                       spr.currency                                           as currency,
                       sp.vendor_id                                           as vendor_id,
                       SUM(spr.value)                                         as value,
                       GROUP_CONCAT(DISTINCT sp.id)                           as purchase_ids,
                       COUNT(DISTINCT sp.id)                                  as purchase_count
                FROM smartcard_purchase AS sp
                         INNER JOIN smartcard_purchase_record AS spr ON sp.id = spr.smartcard_purchase_id
                         LEFT JOIN assistance a on sp.assistance_id = a.id
                WHERE sp.redemption_batch_id IS NULL
                  AND vendor_id IS NOT NULL
                  AND currency IS NOT NULL
                GROUP BY spr.currency, a.project_id, sp.vendor_id
                ORDER BY spr.currency, a.project_id, sp.vendor_id;
        SQL
        );
    }
}
