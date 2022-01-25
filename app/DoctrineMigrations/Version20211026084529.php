<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211026084529 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE assistance ADD food_limit DECIMAL(10, 2) DEFAULT NULL, ADD non_food_limit DECIMAL(10, 2) DEFAULT NULL, ADD cashback_limit DECIMAL(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE assistance RENAME INDEX uniq_1b4f85f2e48efe78 TO UNIQ_1B4F85F2A68DFFCF');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE assistance DROP food_limit, DROP non_food_limit, DROP cashback_limit');
        $this->addSql('ALTER TABLE assistance RENAME INDEX uniq_1b4f85f2a68dffcf TO UNIQ_1B4F85F2E48EFE78');
    }
}
