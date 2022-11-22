<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220201192438 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            '
            UPDATE smartcard_purchase sp
            JOIN smartcard s ON s.id = sp.smartcard_id
            JOIN smartcard_deposit sd ON sd.id = (
                SELECT dep.id
                FROM smartcard_deposit dep
                WHERE sp.used_at > dep.distributed_at AND dep.smartcard_id = s.id
                ORDER BY dep.distributed_at DESC
                LIMIT 1
            )
            LEFT JOIN relief_package rp ON rp.id = sd.relief_package_id
            LEFT JOIN distribution_beneficiary db ON db.id = rp.assistance_beneficiary_id
            LEFT JOIN assistance a ON a.id = db.assistance_id
            SET sp.assistance_id = a.id
        '
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE smartcard_purchase sp SET sp.assistance_id = NULL');
    }
}
