<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211013093346 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP VIEW view_assistance_statistics');
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
            LEFT JOIN relief_package pack ON pack.assistance_beneficiary_id=db.id
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
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(true, 'Cant be downgraded');
    }
}
