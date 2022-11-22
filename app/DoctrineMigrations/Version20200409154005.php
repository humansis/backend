<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200409154005 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            '
            CREATE TABLE community (
                id INT AUTO_INCREMENT NOT NULL,
                address_id INT DEFAULT NULL,
                contact_name VARCHAR(255) DEFAULT NULL,
                phone_number VARCHAR(45) DEFAULT NULL,
                phone_prefix VARCHAR(45) DEFAULT NULL,
                id_number VARCHAR(255) DEFAULT NULL,
                id_type VARCHAR(45) DEFAULT NULL,
                latitude VARCHAR(45) DEFAULT NULL,
                longitude VARCHAR(45) DEFAULT NULL,
                archived TINYINT(1) DEFAULT \'0\' NOT NULL,
                UNIQUE INDEX UNIQ_1B604033F5B7AF75 (address_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_1B604033F5B7AF75 FOREIGN KEY (address_id)
                    REFERENCES address (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE community');
    }
}
