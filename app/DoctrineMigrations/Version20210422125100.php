<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210422125100 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE assistance RENAME INDEX idx_a54e7fd764d218e TO IDX_1B4F85F264D218E');
        $this->addSql('ALTER TABLE assistance RENAME INDEX idx_a54e7fd7166d1f9c TO IDX_1B4F85F2166D1F9C');
        $this->addSql('ALTER TABLE reporting_distribution RENAME INDEX idx_ec84c5186eb6ddb5 TO IDX_EC84C5187096529A');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE assistance RENAME INDEX idx_1b4f85f2166d1f9c TO IDX_A54E7FD7166D1F9C');
        $this->addSql('ALTER TABLE assistance RENAME INDEX idx_1b4f85f264d218e TO IDX_A54E7FD764D218E');
        $this->addSql('ALTER TABLE reporting_distribution RENAME INDEX idx_ec84c5187096529a TO IDX_EC84C5186EB6DDB5');
    }
}
