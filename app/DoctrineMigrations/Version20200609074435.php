<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200609074435 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE community ADD national_id_id INT DEFAULT NULL, DROP id_number, DROP id_type');
        $this->addSql('ALTER TABLE community ADD CONSTRAINT FK_1B604033E9E9E294 FOREIGN KEY (national_id_id) REFERENCES national_id (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1B604033E9E9E294 ON community (national_id_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE community DROP FOREIGN KEY FK_1B604033E9E9E294');
        $this->addSql('DROP INDEX UNIQ_1B604033E9E9E294 ON community');
        $this->addSql(
            'ALTER TABLE community ADD id_number VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, ADD id_type VARCHAR(45) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, DROP national_id_id'
        );
    }
}
