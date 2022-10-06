<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210305000523 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE household ADD proxy_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE household ADD CONSTRAINT FK_54C32FC0DB26A4E FOREIGN KEY (proxy_id) REFERENCES person (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_54C32FC0DB26A4E ON household (proxy_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE household DROP FOREIGN KEY FK_54C32FC0DB26A4E');
        $this->addSql('DROP INDEX UNIQ_54C32FC0DB26A4E ON household');
        $this->addSql('ALTER TABLE household DROP proxy_id');
    }
}
