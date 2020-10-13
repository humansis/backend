<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200722075415 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE smartcard ADD suspicious TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE smartcard ADD suspicious_reason VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE smartcard CHANGE beneficiary_id beneficiary_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE smartcard CHANGE created_at created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE smartcard DROP suspicious');
        $this->addSql('ALTER TABLE smartcard DROP suspicious_reason');
        $this->addSql('ALTER TABLE smartcard CHANGE beneficiary_id beneficiary_id INT NOT NULL');
        $this->addSql('ALTER TABLE smartcard CHANGE created_at created_at DATETIME NOT NULL');
    }
}
