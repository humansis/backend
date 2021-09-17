<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210916205428 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE VIEW view_smartcard_purchased_item AS
            SELECT
                spr.id AS id,
                db.beneficiary_id,
                db.assistance_id,
                a.project_id,
                a.location_id,
                spr.product_id AS product_id,
                DATE_FORMAT(sd.used_at, "%Y-%m-%dT%TZ") AS date_distribution,
                DATE_FORMAT(sp.used_at, "%Y-%m-%dT%TZ") AS date_purchase,
                s.code AS smartcard_code,
                spr.value AS value,
                sp.vendor_id AS vendor_id,
                LPAD(sbatch.id, 6, 0) AS invoice_number,
                spr.currency AS currency

            FROM distribution_beneficiary db
                     JOIN assistance a ON a.id=db.assistance_id AND a.assistance_type="distribution"
                     JOIN abstract_beneficiary ab ON ab.id=db.beneficiary_id

                -- smartcards
                     LEFT JOIN smartcard_deposit sd ON sd.distribution_beneficiary_id=db.id
                     LEFT JOIN smartcard s ON s.id=sd.smartcard_id
                     LEFT JOIN smartcard_purchase sp ON s.id=sp.smartcard_id
                     LEFT JOIN smartcard_purchase_record spr ON sp.id=spr.smartcard_purchase_id
                     LEFT JOIN smartcard_redemption_batch sbatch ON sp.redemption_batch_id=sbatch.id

            WHERE spr.id IS NOT NULL;
        ');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP VIEW view_purchased_smartcard_item');
    }
}
