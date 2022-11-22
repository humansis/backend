<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200504164401 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            '
            CREATE TABLE smartcard (
                id INT AUTO_INCREMENT NOT NULL,
                beneficiary_id INT NOT NULL,
                code VARCHAR(7) NOT NULL,
                state VARCHAR(10) NOT NULL,
                created_at DATETIME NOT NULL,
                UNIQUE INDEX UNIQ_34E0B48F77153098 (code),
                INDEX IDX_34E0B48FECCAAFA0 (beneficiary_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_34E0B48FECCAAFA0 FOREIGN KEY (beneficiary_id)
                    REFERENCES beneficiary (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB
         '
        );

        $this->addSql(
            '
            CREATE TABLE smartcard_record (
                id INT AUTO_INCREMENT NOT NULL,
                smartcard_id INT DEFAULT NULL,
                product_id INT DEFAULT NULL,
                value NUMERIC(10, 2) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_CA9F1B43AC8B107D (smartcard_id),
                INDEX IDX_CA9F1B434584665A (product_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_CA9F1B43AC8B107D FOREIGN KEY (smartcard_id)
                    REFERENCES smartcard (id),
                CONSTRAINT FK_CA9F1B434584665A FOREIGN KEY (product_id)
                    REFERENCES product (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB
         '
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE smartcard_record DROP FOREIGN KEY FK_CA9F1B43AC8B107D');
        $this->addSql('DROP TABLE smartcard_record');
        $this->addSql('DROP TABLE smartcard');
    }
}
