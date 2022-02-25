<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211230153350 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE product ADD created_at DATETIME NOT NULL DEFAULT NOW() COMMENT \'(DC2Type:datetime_immutable)\', ADD modified_at DATETIME NOT NULL DEFAULT NOW() COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE product RENAME INDEX fk_d34a04adbe6903fd TO IDX_D34A04ADBE6903FD');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE product DROP created_at, DROP modified_at');
        $this->addSql('ALTER TABLE product RENAME INDEX idx_d34a04adbe6903fd TO FK_D34A04ADBE6903FD');
    }
}
