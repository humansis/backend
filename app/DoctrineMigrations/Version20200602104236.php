<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200602104236 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            '
            CREATE TABLE voucher_purchase (
                `id` INT AUTO_INCREMENT NOT NULL,
                `vendor_id` INT NOT NULL,
                `used_at` DATETIME DEFAULT NULL,
                PRIMARY KEY(id),
                INDEX IDX_FFB089B2F603EE73 (vendor_id),
                CONSTRAINT FK_FFB089B2F603EE73 FOREIGN KEY (vendor_id)
                    REFERENCES vendor (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB
        '
        );

        $this->addSql(
            '
            CREATE TABLE voucher_purchase_record (
                `id` INT AUTO_INCREMENT NOT NULL,
                `voucher_purchase_id` INT NOT NULL,
                `product_id` INT NOT NULL,
                `value` NUMERIC(10, 2) DEFAULT NULL,
                `quantity` NUMERIC(10, 2) DEFAULT NULL,
                PRIMARY KEY(id),
                INDEX IDX_22906D6E81BB7F3F (voucher_purchase_id),
                INDEX IDX_22906D6E4584665A (product_id),
                UNIQUE INDEX UNIQ_22906D6E81BB7F3F4584665A (voucher_purchase_id, product_id),
                CONSTRAINT FK_22906D6E81BB7F3F FOREIGN KEY (voucher_purchase_id)
                    REFERENCES voucher_purchase (id),
                CONSTRAINT FK_22906D6E4584665A FOREIGN KEY (product_id)
                    REFERENCES product (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB
        '
        );

        $this->addSql([
            'ALTER TABLE voucher ADD voucher_purchase_id INT DEFAULT NULL',
            'ALTER TABLE voucher ADD CONSTRAINT FK_1392A5D881BB7F3F FOREIGN KEY (voucher_purchase_id) REFERENCES voucher_purchase (id)',
            'CREATE INDEX IDX_1392A5D881BB7F3F ON voucher (voucher_purchase_id)',
        ]);

        $this->addSql(
            '
            CREATE TEMPORARY TABLE tmp_purchase
                SELECT v.id AS voucher_id, v.id AS purchase_id, v.vendor_id, v.used_at, vr.product_id, vr.value, vr.quantity
                FROM voucher v
                LEFT JOIN voucher_record vr ON vr.voucher_id=v.id
                WHERE v.used_at IS NOT NULL
        '
        );

        $this->addSql(
            '
            INSERT INTO voucher_purchase(id, vendor_id, used_at)
                SELECT purchase_id, vendor_id, used_at
                FROM tmp_purchase
                GROUP BY purchase_id, vendor_id, used_at
         '
        );

        $this->addSql(
            '
            INSERT INTO voucher_purchase_record(voucher_purchase_id, product_id, quantity, value)
                SELECT purchase_id, product_id, quantity, `value`
                FROM tmp_purchase
                WHERE product_id IS NOT NULL
        '
        );

        $this->addSql('UPDATE voucher v JOIN tmp_purchase t ON v.id=t.voucher_id SET v.voucher_purchase_id=t.purchase_id');

        $this->addSql(
            '
            SELECT @max := MAX(ID) + 1 FROM voucher_purchase;
            PREPARE stmt FROM \'ALTER TABLE voucher_purchase AUTO_INCREMENT = ?\';
            EXECUTE stmt USING @max;
            DEALLOCATE PREPARE stmt;
        '
        );

        $this->addSql([
            'ALTER TABLE voucher DROP FOREIGN KEY FK_1392A5D8F603EE73',
            'DROP INDEX IDX_1392A5D8F603EE73 ON voucher',
            'ALTER TABLE voucher DROP vendor_id, DROP used_at',
        ]);

        $this->addSql([
            'DROP TABLE voucher_product',
            'DROP TABLE voucher_record',
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE voucher_purchase_record DROP FOREIGN KEY FK_22906D6E81BB7F3F');
        $this->addSql(
            'CREATE TABLE voucher_product (voucher_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_10872EAA4584665A (product_id), INDEX IDX_10872EAA28AA1B6F (voucher_id), PRIMARY KEY(voucher_id, product_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' '
        );
        $this->addSql(
            'CREATE TABLE voucher_record (id INT AUTO_INCREMENT NOT NULL, voucher_id INT DEFAULT NULL, product_id INT DEFAULT NULL, used_at DATETIME DEFAULT NULL, value NUMERIC(10, 2) DEFAULT NULL, quantity NUMERIC(10, 2) DEFAULT NULL, UNIQUE INDEX UNIQ_5A90396C28AA1B6F4584665A (voucher_id, product_id), INDEX IDX_5A90396C28AA1B6F (voucher_id), INDEX IDX_5A90396C4584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' '
        );
        $this->addSql('ALTER TABLE voucher_product ADD CONSTRAINT FK_10872EAA28AA1B6F FOREIGN KEY (voucher_id) REFERENCES voucher (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE voucher_product ADD CONSTRAINT FK_10872EAA4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE voucher_record ADD CONSTRAINT FK_5A90396C28AA1B6F FOREIGN KEY (voucher_id) REFERENCES voucher (id)');
        $this->addSql('ALTER TABLE voucher_record ADD CONSTRAINT FK_5A90396C4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('DROP TABLE voucher_purchase');
        $this->addSql('DROP TABLE voucher_purchase_record');
        $this->addSql('ALTER TABLE voucher DROP FOREIGN KEY FK_1392A5D881BB7F3F');
        $this->addSql('DROP INDEX IDX_1392A5D881BB7F3F ON voucher');
        $this->addSql('ALTER TABLE voucher DROP voucher_purchase_id');
        $this->addSql('ALTER TABLE voucher ADD vendor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE voucher ADD CONSTRAINT FK_1392A5D8F603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (id)');
        $this->addSql('CREATE INDEX IDX_1392A5D8F603EE73 ON voucher (vendor_id)');
    }
}
