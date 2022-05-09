<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220427173332 extends AbstractMigration
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
                                'Mobile Money',
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
                                mt.name='Mobile Money'
                            GROUP BY db.id, c.id
                            ;
        ");
        // connect transactions and packages
        $this->addSql('UPDATE transaction t SET relief_package_id=(SELECT id FROM assistance_relief_package WHERE assistance_beneficiary_id=t.distribution_beneficiary_id LIMIT 1);');
        // start distribution of packages which started sending
        $this->addSql('UPDATE assistance_relief_package rp SET state=\'Distribution in progress\' WHERE
                EXISTS (SELECT id FROM transaction WHERE relief_package_id=rp.id) AND rp.modality_type=\'Mobile Money\';');
        // count sent money
        $this->addSql('UPDATE assistance_relief_package rp
                            SET amount_distributed=(
                                COALESCE((SELECT SUM(t.amount_sent)
                                    FROM transaction t
                                    WHERE assistance_beneficiary_id=t.distribution_beneficiary_id AND t.pickup_date IS NOT NULL AND
                                          t.transaction_status=1), 0)
                            )
                            WHERE rp.modality_type=\'Mobile Money\';');
        // set last update date
        $this->addSql('UPDATE assistance_relief_package rp
                            INNER JOIN transaction t2 on rp.id = t2.relief_package_id
                            SET modified_at=(
                                SELECT MAX(t.date_sent)
                                FROM transaction t
                                WHERE assistance_beneficiary_id=t.distribution_beneficiary_id
                            )
                            WHERE rp.modality_type=\'Mobile Money\';');
        $this->addSql('UPDATE assistance_relief_package rp
                            INNER JOIN transaction t2 on rp.id = t2.relief_package_id
                            SET modified_at=(
                                SELECT MAX(t.pickup_date)
                                FROM transaction t
                                WHERE assistance_beneficiary_id=t.distribution_beneficiary_id
                            )
                            WHERE rp.modality_type=\'Mobile Money\';');
        // set completed packages as distributed
        $this->addSql('UPDATE assistance_relief_package rp SET state=\'Distributed\' WHERE rp.amount_distributed>=rp.amount_to_distribute
                            AND rp.modality_type=\'Mobile Money\';');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf(true, 'Cant be downgraded.');
    }
}
