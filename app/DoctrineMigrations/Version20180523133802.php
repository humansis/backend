<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180523133802 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE account (id INT AUTO_INCREMENT NOT NULL, accountnumber VARCHAR(45) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transaction (id INT AUTO_INCREMENT NOT NULL, financial_service_configuration_id INT DEFAULT NULL, commodity_distribution_beneficiary_id INT DEFAULT NULL, transactionIdServiceProvider VARCHAR(45) NOT NULL, amountsent DOUBLE PRECISION NOT NULL, statusServiceprovider TINYINT(1) NOT NULL, transactiontime DATETIME NOT NULL, moneyReceived DOUBLE PRECISION NOT NULL, pickupdate DATETIME NOT NULL, INDEX IDX_723705D16FC5BF88 (financial_service_configuration_id), INDEX IDX_723705D1F45FC34E (commodity_distribution_beneficiary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE financial_service_configuration (id INT AUTO_INCREMENT NOT NULL, account_id INT DEFAULT NULL, service VARCHAR(45) NOT NULL, INDEX IDX_F18E00029B6B5FBA (account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D16FC5BF88 FOREIGN KEY (financial_service_configuration_id) REFERENCES financial_service_configuration (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1F45FC34E FOREIGN KEY (commodity_distribution_beneficiary_id) REFERENCES commodity_distribution_beneficiary (id)');
        $this->addSql('ALTER TABLE financial_service_configuration ADD CONSTRAINT FK_F18E00029B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE financial_service_configuration DROP FOREIGN KEY FK_F18E00029B6B5FBA');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D16FC5BF88');
        $this->addSql('DROP TABLE account');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE financial_service_configuration');
    }
}
