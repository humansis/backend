<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230112102403 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE beneficiary ADD vulnerability_criterion LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('DROP TABLE beneficiary_vulnerability_criterion');
        $this->addSql('DROP TABLE vulnerability_criterion');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql(
            '
            CREATE TABLE vulnerability_criterion (
                id INT AUTO_INCREMENT NOT NULL,
                field_string VARCHAR(255) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );

        $this->addSql('ALTER TABLE vulnerability_criterion ADD active TINYINT(1) NOT NULL DEFAULT 1');

        $this->addSql(
            '
            CREATE TABLE beneficiary_vulnerability_criterion (
                beneficiary_id INT NOT NULL,
                vulnerability_criterion_id INT NOT NULL,
                INDEX IDX_566B5C7ECCAAFA0 (beneficiary_id),
                INDEX IDX_566B5C77255F7BA (vulnerability_criterion_id),
                PRIMARY KEY(beneficiary_id, vulnerability_criterion_id),
                CONSTRAINT FK_566B5C7ECCAAFA0 FOREIGN KEY (beneficiary_id)
                    REFERENCES beneficiary (id)
                    ON DELETE CASCADE,
                CONSTRAINT FK_566B5C77255F7BA FOREIGN KEY (vulnerability_criterion_id)
                    REFERENCES vulnerability_criterion (id)
                    ON DELETE CASCADE
            ) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
    }
}
