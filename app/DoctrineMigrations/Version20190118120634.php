<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190118120634 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE vendor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, shop VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_F52233F6F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE voucher (id INT AUTO_INCREMENT NOT NULL, booklet_id INT NOT NULL, vendor_id INT NOT NULL, used TINYINT(1) NOT NULL, code VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_1392A5D877153098 (code), INDEX IDX_1392A5D8668144B3 (booklet_id), INDEX IDX_1392A5D8F603EE73 (vendor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE voucher_product (voucher_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_10872EAA28AA1B6F (voucher_id), INDEX IDX_10872EAA4584665A (product_id), PRIMARY KEY(voucher_id, product_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE booklet (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, number_vouchers INT NOT NULL, individual_value INT NOT NULL, currency VARCHAR(255) NOT NULL, status INT DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_818DB72077153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE booklet_product (booklet_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_950E688C668144B3 (booklet_id), INDEX IDX_950E688C4584665A (product_id), PRIMARY KEY(booklet_id, product_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE voucher ADD CONSTRAINT FK_1392A5D8668144B3 FOREIGN KEY (booklet_id) REFERENCES booklet (id)');
        $this->addSql('ALTER TABLE voucher ADD CONSTRAINT FK_1392A5D8F603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (id)');
        $this->addSql('ALTER TABLE voucher_product ADD CONSTRAINT FK_10872EAA28AA1B6F FOREIGN KEY (voucher_id) REFERENCES voucher (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE voucher_product ADD CONSTRAINT FK_10872EAA4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booklet_product ADD CONSTRAINT FK_950E688C668144B3 FOREIGN KEY (booklet_id) REFERENCES booklet (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booklet_product ADD CONSTRAINT FK_950E688C4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE voucher DROP FOREIGN KEY FK_1392A5D8F603EE73');
        $this->addSql('ALTER TABLE voucher_product DROP FOREIGN KEY FK_10872EAA28AA1B6F');
        $this->addSql('ALTER TABLE voucher DROP FOREIGN KEY FK_1392A5D8668144B3');
        $this->addSql('ALTER TABLE booklet_product DROP FOREIGN KEY FK_950E688C668144B3');
        $this->addSql('ALTER TABLE voucher_product DROP FOREIGN KEY FK_10872EAA4584665A');
        $this->addSql('ALTER TABLE booklet_product DROP FOREIGN KEY FK_950E688C4584665A');
        $this->addSql('DROP TABLE vendor');
        $this->addSql('DROP TABLE voucher');
        $this->addSql('DROP TABLE voucher_product');
        $this->addSql('DROP TABLE booklet');
        $this->addSql('DROP TABLE booklet_product');
        $this->addSql('DROP TABLE product');
    }
}
