<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190214100312 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_F52233F6F85E0677 ON vendor');
        $this->addSql('ALTER TABLE vendor ADD user_id INT DEFAULT NULL, DROP username, DROP password');
        $this->addSql('ALTER TABLE vendor ADD CONSTRAINT FK_F52233F6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F52233F6A76ED395 ON vendor (user_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE vendor DROP FOREIGN KEY FK_F52233F6A76ED395');
        $this->addSql('DROP INDEX UNIQ_F52233F6A76ED395 ON vendor');
        $this->addSql('ALTER TABLE vendor ADD username VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, ADD password VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, DROP user_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F52233F6F85E0677 ON vendor (username)');
    }
}
