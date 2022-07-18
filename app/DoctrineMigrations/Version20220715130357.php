<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220715130357 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE location CHANGE enum_normalized_name enum_normalized_name VARCHAR(255) NOT NULL');
        $this->addSql('CREATE INDEX duplicity ON location (countryISO3, nested_tree_level, enum_normalized_name)');

        //add duplicity to existing records
        $this->addSql('ALTER TABLE location ADD duplicity_count INT DEFAULT 0 NOT NULL');

        $this->addSql(<<<SQL
            UPDATE location l
            JOIN (
                SELECT
                    countryISO3,
                    nested_tree_level,
                    enum_normalized_name,
                    count(enum_normalized_name) as duplicity
                FROM location
                GROUP BY enum_normalized_name, countryISO3, nested_tree_level
            ) d ON l.enum_normalized_name = d.enum_normalized_name
                AND l.countryISO3 = d.countryISO3
                AND l.nested_tree_level = d.nested_tree_level
            SET l.duplicity_count = (d.duplicity - 1);
        SQL);

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE location DROP duplicity_count');
        $this->addSql('DROP INDEX duplicity ON location');
        $this->addSql('ALTER TABLE location CHANGE enum_normalized_name enum_normalized_name VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8_unicode_ci`');
    }
}
