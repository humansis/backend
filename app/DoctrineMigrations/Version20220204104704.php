<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220204104704 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE import_beneficiary_duplicity (member_index INT NOT NULL, queue_id INT NOT NULL, beneficiary_id INT NOT NULL, reasons LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', differences LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', INDEX IDX_CD6A7AF7477B5BAE (queue_id), INDEX IDX_CD6A7AF7ECCAAFA0 (beneficiary_id), PRIMARY KEY(queue_id, member_index, beneficiary_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE import_beneficiary_duplicity ADD CONSTRAINT FK_CD6A7AF7477B5BAE FOREIGN KEY (queue_id) REFERENCES import_queue (id)');
        $this->addSql('ALTER TABLE import_beneficiary_duplicity ADD CONSTRAINT FK_CD6A7AF7ECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES beneficiary (id)');
        $this->addSql('ALTER TABLE import_household_duplicity DROP reasons, CHANGE decide_at decide_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE import_household_duplicity RENAME INDEX idx_cd6a7af7d8e5dfff TO IDX_36C1D6AAD8E5DFFF');
        $this->addSql('ALTER TABLE import_household_duplicity RENAME INDEX idx_cd6a7af7dda5a056 TO IDX_36C1D6AADDA5A056');
        $this->addSql('ALTER TABLE import_household_duplicity RENAME INDEX idx_cd6a7af7a72083d6 TO IDX_36C1D6AAA72083D6');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE import_beneficiary_duplicity');
    }
}
