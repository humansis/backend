<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180613082717 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE beneficiary_profile ADD location_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE beneficiary_profile ADD CONSTRAINT FK_5C8EFFA564D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('CREATE INDEX IDX_5C8EFFA564D218E ON beneficiary_profile (location_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE beneficiary_profile DROP FOREIGN KEY FK_5C8EFFA564D218E');
        $this->addSql('DROP INDEX IDX_5C8EFFA564D218E ON beneficiary_profile');
        $this->addSql('ALTER TABLE beneficiary_profile DROP location_id');
    }
}
