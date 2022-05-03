<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220427182227 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        // all Mobile Money assistances must have one relief package
        $this->addSql("INSERT INTO assistance_relief_package (
                                assistance_beneficiary_id,
                                state,
                                modality_type,
                                amount_to_distribute,
                                amount_distributed,
                                unit,
                                created_at
                            )
                            SELECT
                                db.id,
                                'To distribute',
                                mt.name,
                                c.value,
                                0,
                                c.unit,
                                NOW()
                            FROM
                                distribution_beneficiary db
                                INNER JOIN assistance as a on a.id = db.assistance_id
                                INNER JOIN commodity c on a.id = c.assistance_id
                                INNER JOIN modality_type mt on c.modality_type_id = mt.id
                            WHERE
                                mt.name='QR Code Voucher' OR mt.name='Paper Voucher'
                            GROUP BY db.id, c.id
                            ;
        ");
        // connect transactions and packages
        $this->addSql('UPDATE booklet b SET relief_package_id=(SELECT id FROM assistance_relief_package WHERE assistance_beneficiary_id=b.distribution_beneficiary_id LIMIT 1);');
        // start distribution of packages which started sending
        $this->addSql('UPDATE assistance_relief_package rp SET state=\'Distribution in progress\' WHERE
                EXISTS (SELECT id FROM booklet b WHERE b.relief_package_id=rp.id and (b.status = 1 OR b.status = 2)) AND (rp.modality_type=\'QR Code Voucher\' OR rp.modality_type=\'Paper Voucher\');');
        // count sent money
        $this->addSql('UPDATE assistance_relief_package rp
                            SET amount_distributed=(
                                SELECT IF(SUM(v.value) IS NOT NULL, SUM(v.value), 0)
                                FROM booklet b
                                JOIN voucher v on b.id = v.booklet_id
                                WHERE assistance_beneficiary_id=b.distribution_beneficiary_id AND b.status=2
                            )
                            WHERE (rp.modality_type=\'QR Code Voucher\' OR rp.modality_type=\'Paper Voucher\');');
        // set completed packages as distributed
        $this->addSql('UPDATE assistance_relief_package rp SET state=\'Distributed\' WHERE rp.amount_distributed>=rp.amount_to_distribute
                            AND (rp.modality_type=\'QR Code Voucher\' OR rp.modality_type=\'Paper Voucher\');');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf(true, 'Cant be downgraded.');
    }
}
