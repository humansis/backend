<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201112120130 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'CREATE TABLE smartcard_redemption_batch (
            id INT AUTO_INCREMENT NOT NULL,
            vendor_id INT NOT NULL,
            redeemed_by INT NOT NULL,
            redeemed_at DATETIME NOT NULL,
            value NUMERIC(10, 2) DEFAULT NULL,
            INDEX IDX_62096928F603EE73 (vendor_id),
            INDEX IDX_620969282FBC08BA (redeemed_by),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE smartcard_redemption_batch ADD CONSTRAINT FK_62096928F603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (id)');
        $this->addSql('ALTER TABLE smartcard_redemption_batch ADD CONSTRAINT FK_620969282FBC08BA FOREIGN KEY (redeemed_by) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE smartcard_redemption_batch');
    }
}
