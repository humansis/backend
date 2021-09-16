<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210531112503 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE import_queue CHANGE state state ENUM(\'New\', \'Valid\', \'Invalid\', \'Invalid Exported\', \'Suspicious\', \'To Create\', \'To Update\', \'To Link\', \'To Ignore\') NOT NULL COMMENT \'(DC2Type:enum_import_queue_state)\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE import_queue CHANGE state state ENUM(\'New\', \'Valid\', \'Invalid\', \'Suspicious\', \'To Create\', \'To Update\', \'To Link\', \'To Ignore\') NOT NULL COMMENT \'(DC2Type:enum_import_queue_state)\'');
    }
}
