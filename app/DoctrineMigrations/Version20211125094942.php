<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211125094942 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE import_queue SET state = 'Valid' WHERE state LIKE 'Suspicious'");
        $this->addSql('ALTER TABLE import_queue CHANGE state state ENUM(\'New\', \'Valid\', \'Invalid\', \'Invalid Exported\', \'Identity Candidate\', \'Unique Candidate\', \'Similarity Candidate\', \'To Create\', \'To Update\', \'To Link\', \'To Ignore\', \'Created\', \'Updated\', \'LINKED\') NOT NULL COMMENT \'(DC2Type:enum_import_queue_state)\'');
    }

    public function down(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE import_queue SET state = 'Valid' WHERE state IN ('Identity Candidate', 'Unique Candidate', 'Similarity Candidate')");
        $this->addSql("UPDATE import_queue SET state = 'To Create' WHERE state LIKE 'Created'");
        $this->addSql("UPDATE import_queue SET state = 'To Update' WHERE state LIKE 'Updated'");
        $this->addSql("UPDATE import_queue SET state = 'To Link' WHERE state LIKE 'Linked'");
        $this->addSql('ALTER TABLE import_queue CHANGE state state ENUM(\'New\', \'Valid\', \'Invalid\', \'Invalid Exported\', \'Suspicious\', \'To Create\', \'To Update\', \'To Link\', \'To Ignore\') NOT NULL COMMENT \'(DC2Type:enum_import_queue_state)\'');
    }
}
