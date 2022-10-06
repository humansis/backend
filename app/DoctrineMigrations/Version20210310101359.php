<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210310101359 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE smartcard_redemption_batch ADD project_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE smartcard_redemption_batch ADD CONSTRAINT FK_7BFEF9A166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('CREATE INDEX IDX_7BFEF9A166D1F9C ON smartcard_redemption_batch (project_id)');
        $this->addSql('ALTER TABLE voucher RENAME INDEX fk_1392a5d83a0a9ae7 TO IDX_1392A5D83A0A9AE7');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE smartcard_redemption_batch DROP FOREIGN KEY FK_7BFEF9A166D1F9C');
        $this->addSql('DROP INDEX IDX_7BFEF9A166D1F9C ON smartcard_redemption_batch');
        $this->addSql('ALTER TABLE smartcard_redemption_batch DROP project_id');
    }
}
