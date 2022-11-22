<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220523125420 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'UPDATE assistance_relief_package rp
                            INNER JOIN general_relief_item gri on rp.assistance_beneficiary_id = gri.distribution_beneficiary_id
                            SET rp.distributedAt=(
                                SELECT MAX(gri2.distributedAt)
                                FROM general_relief_item gri2
                                WHERE assistance_beneficiary_id=gri2.distribution_beneficiary_id
                            )
                            WHERE rp.modality_type!=\'Mobile Money\'
                              AND rp.modality_type!=\'Smartcard\'
                              AND rp.modality_type!=\'QR Code Voucher\'
                              AND rp.modality_type!=\'Paper Voucher\'
                              AND rp.distributedAt IS NULL
        ;'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(true, 'No downgrade');
    }
}
