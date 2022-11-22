<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220427143727 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE assistance_relief_package ADD notes VARCHAR(255) DEFAULT NULL, ADD modified_at DATETIME DEFAULT NOW() COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE assistance_relief_package RENAME INDEX idx_181280cd3049e54a TO IDX_C491CA273049E54A');

        // all validated GRI assistances must have one relief package
        $this->addSql(
            "INSERT INTO assistance_relief_package (
                                assistance_beneficiary_id,
                                state,
                                modality_type,
                                amount_to_distribute,
                                amount_distributed,
                                unit,
                                modified_at,
                                created_at,
                                notes
                            )
                            SELECT
                                db.id,
                                'To distribute',
                                mt.name,
                                MIN(c.value),
                                IF(gri.distributedAt IS NULL, 0, IF(MIN(c.value) IS NOT NULL, MIN(c.value), 0)),
                                MIN(c.unit),
                                gri.distributedAt,
                                NOW(),
                                gri.notes
                            FROM
                                distribution_beneficiary db
                                    INNER JOIN general_relief_item gri on db.id = gri.distribution_beneficiary_id
                                    INNER JOIN assistance as a on a.id = db.assistance_id
                                    INNER JOIN commodity c on a.id = c.assistance_id
                                    INNER JOIN modality_type mt on c.modality_type_id = mt.id
                            WHERE
                                    mt.name!='Smartcard'
                            GROUP BY db.id, mt.name, gri.id
                            ;
        "
        );

        $this->addSql('UPDATE assistance_relief_package arp SET arp.state=\'Distribution in progress\' WHERE amount_distributed > 0 AND amount_distributed<arp.amount_to_distribute');
        $this->addSql('UPDATE assistance_relief_package arp SET arp.state=\'Distributed\' WHERE amount_distributed>=arp.amount_to_distribute');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'Cant be downgraded.');
    }
}
