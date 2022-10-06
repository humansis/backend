<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211206115817 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE smartcard_purchase ADD assistance_id INT DEFAULT NULL, CHANGE hash hash LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE smartcard_purchase ADD CONSTRAINT FK_38CC60347096529A FOREIGN KEY (assistance_id) REFERENCES assistance (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE smartcard_purchase DROP FOREIGN KEY FK_38CC60347096529A');
        $this->addSql('DROP INDEX IDX_38CC60347096529A ON smartcard_purchase');
        $this->addSql('ALTER TABLE smartcard_purchase DROP assistance_id, CHANGE hash hash LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`');
    }
}
