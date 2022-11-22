<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201202150426 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'CREATE TABLE voucher_redemption_batch (id INT AUTO_INCREMENT NOT NULL, vendor_id INT NOT NULL, redeemed_by INT DEFAULT NULL, redeemed_at DATETIME NOT NULL, value NUMERIC(10, 2) DEFAULT NULL, INDEX IDX_23067972F603EE73 (vendor_id), INDEX IDX_23067972F203A502 (redeemed_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE voucher_redemption_batch ADD CONSTRAINT FK_23067972F603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (id)');
        $this->addSql('ALTER TABLE voucher_redemption_batch ADD CONSTRAINT FK_23067972F203A502 FOREIGN KEY (redeemed_by) REFERENCES `user` (id)');

        $this->addSql('ALTER TABLE voucher ADD redemption_batch_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE voucher ADD CONSTRAINT FK_1392A5D83A0A9AE7 FOREIGN KEY (redemption_batch_id) REFERENCES voucher_redemption_batch (id)');

        //migrate redeemed vouchers to voucher_redemption_batch
        $this->addSql(
            '
            INSERT INTO voucher_redemption_batch (vendor_id, redeemed_at, value) SELECT voucher_purchase.vendor_id as vendor_id, voucher.redeemed_at AS redeemed_at, SUM(voucher_purchase_record.value) AS value
            FROM voucher
                     INNER JOIN voucher_purchase ON voucher.voucher_purchase_id = voucher_purchase.id
                     INNER JOIN voucher_purchase_record ON voucher_purchase.id = voucher_purchase_record.voucher_purchase_id
            WHERE voucher.redeemed_at IS NOT NULL
            GROUP BY voucher.redeemed_at, voucher_purchase.vendor_id
            '
        );

        //link redeemed voucher to voucher redemption
        $this->addSql(
            '
            UPDATE voucher
                inner join voucher_purchase vp on voucher.voucher_purchase_id = vp.id
                inner join voucher_redemption_batch
                    ON voucher.redeemed_at = voucher_redemption_batch.redeemed_at
                           AND voucher_redemption_batch.vendor_id = vp.vendor_id
            SET redemption_batch_id = voucher_redemption_batch.id
        '
        );

        //remove redeemed_at from voucher
        $this->addSql('ALTER TABLE voucher DROP COLUMN redeemed_at');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE voucher DROP FOREIGN KEY FK_1392A5D83A0A9AE7');
        $this->addSql('DROP TABLE voucher_redemption_batch');

        $this->addSql('ALTER TABLE voucher DROP redemption_batch_id');
        $this->addSql('ALTER TABLE voucher ADD redeemed_at DATETIME DEFAULT NULL');
    }
}
