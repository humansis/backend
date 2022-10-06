<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201029153501 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE assistance CHANGE target_type target_type_bak INT');
        $this->addSql('ALTER TABLE assistance ADD target_type ENUM(\'individual\', \'household\', \'community\', \'institution\') NOT NULL COMMENT \'(DC2Type:enum_assistance_target_type)\'');
        $this->addSql('UPDATE assistance SET target_type=\'individual\' WHERE target_type_bak=1');
        $this->addSql('UPDATE assistance SET target_type=\'household\' WHERE target_type_bak<>1');
        $this->addSql('ALTER TABLE assistance DROP target_type_bak');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'ALTER TABLE assistance CHANGE target_type target_type_bak ENUM(\'individual\', \'household\', \'community\', \'institution\') NOT NULL COMMENT \'(DC2Type:enum_assistance_target_type)\''
        );
        $this->addSql('ALTER TABLE assistance CHANGE target_type target_type INT DEFAULT NULL');
        $this->addSql('UPDATE assistance SET target_type=0 WHERE target_type_bak like \'household\'');
        $this->addSql('UPDATE assistance SET target_type=1 WHERE target_type_bak like \'individual\'');
        $this->addSql('UPDATE assistance SET target_type=2 WHERE target_type_bak like \'community\'');
        $this->addSql('UPDATE assistance SET target_type=3 WHERE target_type_bak like \'institution\'');
        $this->addSql('ALTER TABLE assistance DROP target_type_bak');
    }
}
