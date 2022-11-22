<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200430161738 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE institution ADD national_id_id INT DEFAULT NULL, DROP id_number, DROP id_type');
        $this->addSql('ALTER TABLE institution ADD CONSTRAINT FK_3A9F98E5E9E9E294 FOREIGN KEY (national_id_id) REFERENCES national_id (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3A9F98E5E9E9E294 ON institution (national_id_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE institution DROP FOREIGN KEY FK_3A9F98E5E9E9E294');
        $this->addSql('DROP INDEX UNIQ_3A9F98E5E9E9E294 ON institution');
        $this->addSql(
            'ALTER TABLE institution ADD id_number VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, ADD id_type VARCHAR(45) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, DROP national_id_id'
        );
    }
}
