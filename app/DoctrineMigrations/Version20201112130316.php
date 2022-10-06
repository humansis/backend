<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201112130316 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE smartcard_redemption_batch RENAME INDEX idx_62096928f603ee73 TO IDX_7BFEF9AF603EE73');
        $this->addSql('ALTER TABLE smartcard_redemption_batch RENAME INDEX idx_620969282fbc08ba TO IDX_7BFEF9AF203A502');
        $this->addSql('ALTER TABLE smartcard_purchase ADD redemption_batch_id INT DEFAULT NULL, DROP redeemed_at');
        $this->addSql('ALTER TABLE smartcard_purchase ADD CONSTRAINT FK_38CC60343A0A9AE7 FOREIGN KEY (redemption_batch_id) REFERENCES smartcard_redemption_batch (id)');
        $this->addSql('CREATE INDEX IDX_38CC60343A0A9AE7 ON smartcard_purchase (redemption_batch_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE smartcard_purchase DROP FOREIGN KEY FK_38CC60343A0A9AE7');
        $this->addSql('DROP INDEX IDX_38CC60343A0A9AE7 ON smartcard_purchase');
        $this->addSql('ALTER TABLE smartcard_purchase ADD redeemed_at DATETIME DEFAULT NULL, DROP redemption_batch_id');
        $this->addSql('ALTER TABLE smartcard_redemption_batch RENAME INDEX idx_7bfef9af203a502 TO IDX_620969282FBC08BA');
        $this->addSql('ALTER TABLE smartcard_redemption_batch RENAME INDEX idx_7bfef9af603ee73 TO IDX_62096928F603EE73');
    }
}
