<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230313124022 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql('DROP INDEX only_one_household_answer ON bmstest.country_specific_answer');
        $this->addSql('ALTER TABLE country_specific ADD multi_value TINYINT(1) NOT NULL, CHANGE type type ENUM(\'number\', \'text\') NOT NULL COMMENT \'(DC2Type:enum_country_specific_type)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE country_specific DROP multi_value, CHANGE type type VARCHAR(45) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX duplicity_check_idx ON country_specific (field_string, iso3)');
    }
}
