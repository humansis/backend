<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191108095855 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            '
            CREATE TABLE service (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                parameters JSON NOT NULL,
                country VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );

        $this->addSql(
            '
            CREATE TABLE organization_service (
                id INT AUTO_INCREMENT NOT NULL,
                organization_id INT DEFAULT NULL,
                service_id INT DEFAULT NULL,
                enabled TINYINT(1) NOT NULL,
                parameters_value JSON NOT NULL,
                INDEX IDX_2C4F129132C8A3DE (organization_id),
                INDEX IDX_2C4F1291ED5CA9E6 (service_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_2C4F129132C8A3DE FOREIGN KEY (organization_id)
                    REFERENCES organization (id),
                CONSTRAINT FK_2C4F1291ED5CA9E6 FOREIGN KEY (service_id)
                    REFERENCES service (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE organization_service DROP FOREIGN KEY FK_2C4F1291ED5CA9E6');
        $this->addSql('DROP TABLE service');
        $this->addSql('DROP TABLE organization_service');
    }
}
