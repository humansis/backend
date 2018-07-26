<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180726101817 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE modality (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_307988C05E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE modality_type (id INT AUTO_INCREMENT NOT NULL, modality_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_946534112D6D889B (modality_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE modality_type ADD CONSTRAINT FK_946534112D6D889B FOREIGN KEY (modality_id) REFERENCES modality (id)');
        $this->addSql('ALTER TABLE commodity ADD modality_type_id INT DEFAULT NULL, DROP modality, DROP type, DROP conditions');
        $this->addSql('ALTER TABLE commodity ADD CONSTRAINT FK_5E8D2F74FD576AC3 FOREIGN KEY (modality_type_id) REFERENCES modality_type (id)');
        $this->addSql('CREATE INDEX IDX_5E8D2F74FD576AC3 ON commodity (modality_type_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE modality_type DROP FOREIGN KEY FK_946534112D6D889B');
        $this->addSql('ALTER TABLE commodity DROP FOREIGN KEY FK_5E8D2F74FD576AC3');
        $this->addSql('DROP TABLE modality');
        $this->addSql('DROP TABLE modality_type');
        $this->addSql('DROP INDEX IDX_5E8D2F74FD576AC3 ON commodity');
        $this->addSql('ALTER TABLE commodity ADD modality VARCHAR(45) NOT NULL COLLATE utf8_unicode_ci, ADD type VARCHAR(45) NOT NULL COLLATE utf8_unicode_ci, ADD conditions VARCHAR(45) NOT NULL COLLATE utf8_unicode_ci, DROP modality_type_id');
    }
}
