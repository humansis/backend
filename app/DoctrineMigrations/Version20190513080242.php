<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190513080242 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'ALTER TABLE beneficiary ADD enGivenName VARCHAR(255) DEFAULT NULL, ADD enFamilyName VARCHAR(255) DEFAULT NULL, CHANGE givenName localGivenName VARCHAR(255) DEFAULT NULL, CHANGE familyName localFamilyName VARCHAR(255) DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'ALTER TABLE beneficiary ADD givenName VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, ADD familyName VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, DROP enGivenName, DROP enFamilyName, DROP localGivenName, DROP localFamilyName'
        );
    }
}
