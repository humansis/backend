<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190118142005 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE booklet ADD distribution_beneficiary_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE booklet ADD CONSTRAINT FK_818DB72095AAFAA9 FOREIGN KEY (distribution_beneficiary_id) REFERENCES distribution_beneficiary (id)');
        $this->addSql('CREATE INDEX IDX_818DB72095AAFAA9 ON booklet (distribution_beneficiary_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE booklet DROP FOREIGN KEY FK_818DB72095AAFAA9');
        $this->addSql('DROP INDEX IDX_818DB72095AAFAA9 ON booklet');
        $this->addSql('ALTER TABLE booklet DROP distribution_beneficiary_id');
    }
}
