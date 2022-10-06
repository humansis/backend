<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220520065241 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE assistance_relief_package ADD distributed_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE assistance_relief_package ADD CONSTRAINT FK_C491CA2765F14916 FOREIGN KEY (distributed_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE assistance_relief_package DROP FOREIGN KEY FK_C491CA2765F14916');
        $this->addSql('DROP INDEX IDX_C491CA2765F14916 ON assistance_relief_package');
        $this->addSql('ALTER TABLE assistance_relief_package DROP distributed_by_id');
    }
}
