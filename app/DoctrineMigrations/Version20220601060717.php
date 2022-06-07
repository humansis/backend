<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220601060717 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE smartcard_deposit ADD hash VARCHAR(32) DEFAULT NULL;');
        $this->addSql('
            UPDATE smartcard_deposit sd
                INNER JOIN smartcard s on sd.smartcard_id = s.id
                INNER JOIN assistance_relief_package arp on sd.relief_package_id = arp.id
            SET sd.hash = MD5(CONCAT(s.code, sd.distributed_at, sd.value, arp.unit, arp.id));
        ');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE smartcard_deposit DROP hash;');
    }
}
