<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211013134052 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP VIEW view_distributed_item');
        $this->addSql(
            'CREATE VIEW view_distributed_item AS
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
            LEFT JOIN relief_package pack ON pack.assistance_beneficiary_id=db.id
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
                JOIN voucher_purchase vp ON vp.id=v.voucher_purchase_id
                JOIN voucher_purchase_record vpr ON vpr.voucher_purchase_id=vp.id
                WHERE b.distribution_beneficiary_id IS NOT NULL
                GROUP BY b.id, b.code, b.distribution_beneficiary_id
            ) AS b ON b.distribution_beneficiary_id=db.id

            WHERE (sd.id IS NOT NULL OR gri.id IS NOT NULL OR t.id IS NOT NULL OR b.id IS NOT NULL)
                AND (sd.distributed_at IS NOT NULL OR t.pickup_date IS NOT NULL OR gri.distributedAt IS NOT NULL OR b.used_at)
        '
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(true, 'Cant be downgraded');
    }
}
