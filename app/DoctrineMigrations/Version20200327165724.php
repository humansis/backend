<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200327165724 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            '
            ALTER TABLE household
                ADD assets LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\',
                ADD shelter_status INT DEFAULT NULL,
                ADD dept_level INT DEFAULT NULL,
                ADD support_received_types LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\',
                ADD support_date_received DATETIME DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE household DROP assets');
        $this->addSql('ALTER TABLE household DROP shelter_status');
        $this->addSql('ALTER TABLE household DROP dept_level');
        $this->addSql('ALTER TABLE household DROP support_received_types');
        $this->addSql('ALTER TABLE household DROP support_date_received');
    }
}
