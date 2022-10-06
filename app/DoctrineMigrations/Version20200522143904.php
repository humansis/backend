<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200522143904 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed sIDX_70CFF07FE79FF843afely on \'mysql\'.');

        $this->addSql(
            '
            CREATE TABLE household_activity (
                id INT AUTO_INCREMENT NOT NULL,
                household_id INT DEFAULT NULL,
                author_id INT DEFAULT NULL,
                content JSON NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_70CFF07FE79FF843 (household_id),
                INDEX IDX_70CFF07FF675F31B (author_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_4A4E9A65E79FF843 FOREIGN KEY (household_id)
                    REFERENCES household (id),
                CONSTRAINT FK_4A4E9A65F675F31B FOREIGN KEY (author_id)
                    REFERENCES `user` (id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE household_activity');
    }
}
