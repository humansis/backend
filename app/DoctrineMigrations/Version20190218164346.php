<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190218164346 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE voucher_product');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE voucher_product (voucher_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_10872EAA4584665A (product_id), INDEX IDX_10872EAA28AA1B6F (voucher_id), PRIMARY KEY(voucher_id, product_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE voucher_product ADD CONSTRAINT FK_10872EAA28AA1B6F FOREIGN KEY (voucher_id) REFERENCES voucher (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE voucher_product ADD CONSTRAINT FK_10872EAA4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }
}
