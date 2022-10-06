<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201113120153 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE smartcard ADD currency VARCHAR(3) DEFAULT NULL');
        $this->addSql(
            '
            UPDATE smartcard s
            LEFT JOIN smartcard_deposit sd ON sd.smartcard_id=s.id
            LEFT JOIN distribution_beneficiary db ON db.id=sd.distribution_beneficiary_id
            LEFT JOIN commodity c ON c.assistance_id=db.assistance_id
            LEFT JOIN modality_type mt ON mt.id=c.modality_type_id AND mt.name=\'Smartcard\'
            SET s.currency=c.unit
            WHERE c.unit IS NOT NULL
        '
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE smartcard DROP currency');
    }
}
