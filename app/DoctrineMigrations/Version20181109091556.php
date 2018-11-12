<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181109091556 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE transaction DROP INDEX UNIQ_723705D195AAFAA9, ADD INDEX IDX_723705D195AAFAA9 (distribution_beneficiary_id)');
        $this->addSql('ALTER TABLE transaction ADD sent_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1A45BB98C FOREIGN KEY (sent_by_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_723705D1A45BB98C ON transaction (sent_by_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE transaction DROP INDEX IDX_723705D195AAFAA9, ADD UNIQUE INDEX UNIQ_723705D195AAFAA9 (distribution_beneficiary_id)');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1A45BB98C');
        $this->addSql('DROP INDEX IDX_723705D1A45BB98C ON transaction');
        $this->addSql('ALTER TABLE transaction DROP sent_by_id');
    }
}
