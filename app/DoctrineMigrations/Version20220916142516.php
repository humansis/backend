<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20220916142516 extends AbstractMigration
{

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TEMPORARY TABLE modality_migration SELECT c.id, mt.name FROM commodity c INNER JOIN modality_type mt ON mt.id = c.modality_type_id');
        $this->addSql('ALTER TABLE commodity DROP FOREIGN KEY FK_5E8D2F74FD576AC3');
        $this->addSql('DROP INDEX IDX_5E8D2F74FD576AC3 ON commodity');
        $this->addSql('ALTER TABLE commodity ADD modality_type ENUM(\'Mobile Money\', \'Cash\', \'Smartcard\', \'QR Code Voucher\', \'Paper Voucher\', \'Food Rations\', \'Ready to Eat Rations\', \'Bread\', \'Agricultural Kit\', \'WASH Kit\', \'Shelter tool kit\', \'Hygiene kit\', \'Dignity kit\', \'NFI Kit\', \'Winterization Kit\', \'Activity item\', \'Loan\', \'Business Grant\') DEFAULT NULL COMMENT \'(DC2Type:enum_modality_type)\', DROP modality_type_id');
        $this->addSql('UPDATE commodity INNER JOIN modality_migration ON commodity.id = modality_migration.id SET commodity.modality_type = modality_migration.name');
        $this->addSql('DROP TABLE `modality_type`');
        $this->addSql('DROP TABLE `modality`');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TEMPORARY TABLE modality_migration SELECT c.id, c.modality_type FROM commodity c');
        $this->addSql('ALTER TABLE commodity ADD modality_type_id INT DEFAULT NULL, DROP modality_type');
        $this->addSql('ALTER TABLE commodity ADD CONSTRAINT FK_5E8D2F74FD576AC3 FOREIGN KEY (modality_type_id) REFERENCES modality_type (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_5E8D2F74FD576AC3 ON commodity (modality_type_id)');
        $this->addSql('UPDATE commodity c INNER JOIN modality_migration mm ON c.id = mm.id INNER JOIN modality_type mt ON mt.name = mm.modality_type SET c.modality_type_id = mt.id');
    }

}
