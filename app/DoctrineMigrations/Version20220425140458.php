<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220425140458 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // see view_smartcard_preliminary_invoice.sql
        $this->addSql('CREATE VIEW view_smartcard_preliminary_invoice AS
            SELECT
                a.project_id as project_id,
                spr.currency as currency,
                sp.vendor_id as vendor_id,
                SUM(spr.value) as value,
                GROUP_CONCAT(DISTINCT sp.id) as purchase_ids,
                COUNT(DISTINCT sp.id) as purchase_count
            FROM
                smartcard_purchase AS sp
                    INNER JOIN smartcard_purchase_record AS spr ON sp.id = spr.smartcard_purchase_id
                    LEFT JOIN assistance a on sp.assistance_id = a.id
            WHERE sp.redemption_batch_id IS NULL AND vendor_id IS NOT NULL AND currency IS NOT NULL AND project_id IS NOT NULL
            GROUP BY spr.currency, a.project_id, sp.vendor_id
            ORDER BY spr.currency, a.project_id, sp.vendor_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('DROP VIEW view_smartcard_preliminary_invoice');
    }
}
