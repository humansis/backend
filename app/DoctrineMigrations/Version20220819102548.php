<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220819102548 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'ALTER TABLE commodity CHANGE division division ENUM(
                \'Per Household\',
                \'Per Household Member\',
                \'Per Household Members\'
            ) DEFAULT NULL COMMENT \'(DC2Type:enum_assitance_commodity_division)\''
        );

        $this->addSql(
            'CREATE TABLE division_group (id INT AUTO_INCREMENT NOT NULL, commodity_id INT NOT NULL, range_from INT NOT NULL, range_to INT DEFAULT NULL, value NUMERIC(10, 0) NOT NULL, INDEX IDX_4484A0DBB4ACC212 (commodity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE division_group ADD CONSTRAINT FK_4484A0DBB4ACC212 FOREIGN KEY (commodity_id) REFERENCES commodity (id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'Can not be downgraded');
    }
}
