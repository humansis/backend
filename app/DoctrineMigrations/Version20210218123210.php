<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210218123210 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            '
            ALTER TABLE smartcard_purchase_record
                ADD currency VARCHAR(255) DEFAULT NULL,
                CHANGE value value NUMERIC(10, 2) NOT NULL
        '
        );
        $this->addSql(
            '
            UPDATE smartcard_purchase_record spr
                JOIN smartcard_purchase sp on sp.id=spr.smartcard_purchase_id
                JOIN smartcard s on s.id=sp.smartcard_id
                SET spr.currency=s.currency
        '
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE smartcard_purchase_record DROP currency, CHANGE value value NUMERIC(10, 2) DEFAULT NULL');
    }
}
