<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200415083057 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            '
            CREATE TABLE voucher_record (
                id INT AUTO_INCREMENT NOT NULL,
                voucher_id INT DEFAULT NULL,
                product_id INT DEFAULT NULL,
                used_at DATETIME DEFAULT NULL,
                value NUMERIC(10, 2) DEFAULT NULL,
                quantity NUMERIC(10, 2) DEFAULT NULL,
                INDEX IDX_5A90396C28AA1B6F (voucher_id),
                INDEX IDX_5A90396C4584665A (product_id),
                UNIQUE INDEX UNIQ_5A90396C28AA1B6F4584665A (voucher_id, product_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_5A90396C28AA1B6F FOREIGN KEY (voucher_id)
                    REFERENCES voucher (id),
                CONSTRAINT FK_5A90396C4584665A FOREIGN KEY (product_id)
                    REFERENCES product (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );

        $this->addSql(
            '
            INSERT INTO voucher_record(voucher_id, product_id, `used_at`, `value`)
                SELECT vp.voucher_id, vp.product_id, v.used_at, vv.`value`
                FROM voucher_product vp
                JOIN voucher v ON v.id=vp.voucher_id
                LEFT JOIN (
                    SELECT vp_i.voucher_id, MIN(vp_i.product_id) AS product_id, v_i.`value`
                    FROM voucher_product vp_i
                    LEFT JOIN voucher v_i ON v_i.id=vp_i.voucher_id
                    GROUP BY vp_i.voucher_id
                ) AS vv ON vv.voucher_id=vp.voucher_id AND vv.product_id=vp.product_id'
        );

        $schema->getTable('voucher_product')->setComment('deprecated');
        $schema->getTable('voucher')->getColumn('value')->setComment('deprecated');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE voucher_record');
        $this->addSql('DROP INDEX UNIQ_5A90396C28AA1B6F4584665A ON voucher_record');

        $schema->getTable('voucher_product')->setComment('');
        $schema->getTable('voucher')->getColumn('value')->setComment('');
    }
}
