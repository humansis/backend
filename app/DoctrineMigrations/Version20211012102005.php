<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211012102005 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE assistance_beneficiary_commodity (id INT AUTO_INCREMENT NOT NULL, assistance_beneficiary_id INT DEFAULT NULL, state ENUM(\'To distribute\', \'Distribution in progress\', \'Distributed\', \'Expired\', \'Canceled\') NOT NULL COMMENT \'(DC2Type:enum_assistance_beneficiary_commodity_state)\', modality_type ENUM(\'Mobile Money\', \'Cash\', \'Smartcard\', \'QR Code Voucher\', \'Paper Voucher\', \'Food Rations\', \'Ready to Eat Rations\', \'Bread\', \'Agricultural Kit\', \'WASH Kit\', \'Shelter tool kit\', \'Hygiene kit\', \'Dignity kit\', \'NFI Kit\', \'Winterization Kit\', \'Activity item\', \'Loan\', \'Business Grant\') NOT NULL COMMENT \'(DC2Type:enum_modality_type)\', amount_to_distribute NUMERIC(10, 2) NOT NULL, amount_distributed NUMERIC(10, 2) NOT NULL, unit VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_82B31F853049E54A (assistance_beneficiary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE assistance_beneficiary_commodity ADD CONSTRAINT FK_82B31F853049E54A FOREIGN KEY (assistance_beneficiary_id) REFERENCES distribution_beneficiary (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE assistance_beneficiary_commodity');
    }
}
