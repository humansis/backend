<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211230160106 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE donor ADD created_at DATETIME NOT NULL DEFAULT NOW() COMMENT \'(DC2Type:datetime_immutable)\', ADD modified_at DATETIME NOT NULL DEFAULT NOW() COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('UPDATE donor SET created_at=dateAdded WHERE dateAdded IS NOT NULL');
        $this->addSql('UPDATE donor SET modified_at=dateAdded WHERE dateAdded IS NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE donor DROP created_at, DROP modified_at');
    }
}
