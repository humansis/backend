<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200416154457 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE booklet ADD project_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE booklet ADD CONSTRAINT FK_818DB720166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('CREATE INDEX IDX_818DB720166D1F9C ON booklet (project_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE booklet DROP FOREIGN KEY FK_818DB720166D1F9C');
        $this->addSql('DROP INDEX IDX_818DB720166D1F9C ON booklet');
        $this->addSql('ALTER TABLE booklet DROP project_id');
    }
}
