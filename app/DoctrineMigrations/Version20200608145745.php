<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200608145745 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            '
            CREATE TABLE smartcard_deposit (
                id INT AUTO_INCREMENT NOT NULL,
                smartcard_id INT NOT NULL,
                value NUMERIC(10, 2) NOT NULL,
                depositor_id INT NOT NULL,
                used_at DATETIME DEFAULT NULL,
                INDEX IDX_FD578545AC8B107D (smartcard_id),
                INDEX IDX_FD578545EB8724B4 (depositor_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_FD578545AC8B107D FOREIGN KEY (smartcard_id)
                    REFERENCES smartcard (id),
                CONSTRAINT FK_FD578545EB8724B4 FOREIGN KEY (depositor_id)
                    REFERENCES `user` (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );

        $this->addSql(
            '
            CREATE TABLE smartcard_purchase (
                id INT AUTO_INCREMENT NOT NULL,
                smartcard_id INT NOT NULL,
                vendor_id INT NOT NULL,
                used_at DATETIME DEFAULT NULL,
                INDEX IDX_38CC6034AC8B107D (smartcard_id),
                INDEX IDX_38CC6034F603EE73 (vendor_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_38CC6034AC8B107D FOREIGN KEY (smartcard_id)
                    REFERENCES smartcard (id),
                CONSTRAINT FK_38CC6034F603EE73 FOREIGN KEY (vendor_id)
                    REFERENCES vendor (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );

        $this->addSql(
            '
            CREATE TABLE smartcard_purchase_record (
                id INT AUTO_INCREMENT NOT NULL,
                smartcard_purchase_id INT NOT NULL,
                product_id INT NOT NULL,
                value NUMERIC(10, 2) DEFAULT NULL,
                quantity NUMERIC(10, 2) DEFAULT NULL,
                INDEX IDX_2FA5239DB20C6D78 (smartcard_purchase_id),
                INDEX IDX_2FA5239D4584665A (product_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_2FA5239DB20C6D78 FOREIGN KEY (smartcard_purchase_id)
                    REFERENCES smartcard_purchase (id),
                CONSTRAINT FK_2FA5239D4584665A FOREIGN KEY (product_id)
                    REFERENCES product (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );

        $this->addSql(
            '
            INSERT INTO smartcard_purchase(id, vendor_id, used_at)
                SELECT id, NULL, created_at
                FROM smartcard_record
         '
        );

        $this->addSql(
            '
            INSERT INTO smartcard_purchase_record(smartcard_purchase_id, product_id, quantity, `value`)
                SELECT id, product_id, quantity, `value`
                FROM smartcard_record
        '
        );

        $this->addSql('DROP TABLE smartcard_record');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE smartcard_purchase_record DROP FOREIGN KEY FK_2FA5239DB20C6D78');
        $this->addSql(
            'CREATE TABLE smartcard_record (id INT AUTO_INCREMENT NOT NULL, smartcard_id INT DEFAULT NULL, product_id INT DEFAULT NULL, value NUMERIC(10, 2) NOT NULL, created_at DATETIME NOT NULL, quantity NUMERIC(10, 2) DEFAULT NULL, INDEX IDX_CA9F1B434584665A (product_id), INDEX IDX_CA9F1B43AC8B107D (smartcard_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' '
        );
        $this->addSql('ALTER TABLE smartcard_record ADD CONSTRAINT FK_CA9F1B434584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE smartcard_record ADD CONSTRAINT FK_CA9F1B43AC8B107D FOREIGN KEY (smartcard_id) REFERENCES smartcard (id)');
        $this->addSql('DROP TABLE smartcard_purchase_record');
        $this->addSql('DROP TABLE smartcard_purchase');
        $this->addSql('DROP TABLE smartcard_deposit');
    }
}
