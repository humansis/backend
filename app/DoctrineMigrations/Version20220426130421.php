<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220426130421 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE booklet ADD beneficiary_assigned_by INT DEFAULT NULL, ADD beneficiary_assigned_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE booklet ADD CONSTRAINT FK_818DB720819B6A12 FOREIGN KEY (beneficiary_assigned_by) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_818DB720819B6A12 ON booklet (beneficiary_assigned_by)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE booklet DROP FOREIGN KEY FK_818DB720819B6A12');
        $this->addSql('DROP INDEX IDX_818DB720819B6A12 ON booklet');
        $this->addSql('ALTER TABLE booklet DROP beneficiary_assigned_by, DROP beneficiary_assigned_at');
    }
}
