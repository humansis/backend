<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220210140108 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE import_beneficiary_duplicity ADD id INT AUTO_INCREMENT NOT NULL, CHANGE queue_id queue_id INT DEFAULT NULL, CHANGE beneficiary_id beneficiary_id INT DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE import_beneficiary_duplicity MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE import_beneficiary_duplicity DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE import_beneficiary_duplicity DROP id, CHANGE queue_id queue_id INT NOT NULL, CHANGE beneficiary_id beneficiary_id INT NOT NULL');
        $this->addSql('ALTER TABLE import_beneficiary_duplicity ADD PRIMARY KEY (queue_id, member_index, beneficiary_id)');
    }
}
