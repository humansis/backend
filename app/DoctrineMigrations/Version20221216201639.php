<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221216201639 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE smartcard_deposit CHANGE hash hash VARCHAR(255) NOT NULL');

        $this->addSql('
            UPDATE smartcard_deposit sd
                INNER JOIN smartcard s on sd.smartcard_id = s.id
                INNER JOIN assistance_relief_package arp on sd.relief_package_id = arp.id
            SET sd.hash = MD5(CONCAT(s.code, "-", sd.value, "-", arp.unit, "-", arp.id));
        ');

        $this->addSql('CREATE UNIQUE INDEX unique_deposit_hash ON smartcard_deposit (hash)');

        $this->addSql('CREATE TABLE smartcard_deposit_log (id INT AUTO_INCREMENT NOT NULL, smartcard_deposit_id INT DEFAULT NULL, created_by_user_id INT DEFAULT NULL, request_data JSON NOT NULL, message VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_BF964569A3EB22BF (smartcard_deposit_id), INDEX IDX_BF9645697D182D95 (created_by_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE smartcard_deposit_log ADD CONSTRAINT FK_BF964569A3EB22BF FOREIGN KEY (smartcard_deposit_id) REFERENCES smartcard_deposit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE smartcard_deposit_log ADD CONSTRAINT FK_BF9645697D182D95 FOREIGN KEY (created_by_user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            UPDATE smartcard_deposit sd
                INNER JOIN smartcard s on sd.smartcard_id = s.id
                INNER JOIN assistance_relief_package arp on sd.relief_package_id = arp.id
            SET sd.hash = MD5(CONCAT(s.code, "-", sd.distributed_at, "-", sd.value, "-", arp.unit, "-", arp.id));
        ');

        $this->addSql('DROP INDEX unique_deposit_hash ON smartcard_deposit');

        $this->addSql('ALTER TABLE smartcard_deposit_log DROP FOREIGN KEY FK_BF964569A3EB22BF');
        $this->addSql('ALTER TABLE smartcard_deposit_log DROP FOREIGN KEY FK_BF9645697D182D95');
        $this->addSql('DROP TABLE smartcard_deposit_log');
    }
}
