<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190527124255 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'CREATE TABLE referral (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, comment VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE beneficiary ADD referral_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE beneficiary ADD CONSTRAINT FK_7ABF446A3CCAA4B7 FOREIGN KEY (referral_id) REFERENCES referral (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7ABF446A3CCAA4B7 ON beneficiary (referral_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE beneficiary DROP FOREIGN KEY FK_7ABF446A3CCAA4B7');
        $this->addSql('DROP TABLE referral');
        $this->addSql('DROP INDEX UNIQ_7ABF446A3CCAA4B7 ON beneficiary');
        $this->addSql('ALTER TABLE beneficiary DROP referral_id');
    }
}
