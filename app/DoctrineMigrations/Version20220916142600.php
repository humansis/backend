<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220916142600 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE location CHANGE enum_normalized_name enum_normalized_name VARCHAR(255) NOT NULL');
        $this->addSql('CREATE INDEX duplicity ON location (iso3, nested_tree_level, enum_normalized_name)');

        //add duplicity to existing records
        $this->addSql('ALTER TABLE location ADD duplicity_count INT DEFAULT 0 NOT NULL');

        $this->addSql(<<<SQL
            CREATE PROCEDURE updateLocationDuplicity()
            BEGIN
                UPDATE location l
                JOIN (
                    SELECT
                        iso3,
                        nested_tree_level,
                        enum_normalized_name,
                        count(enum_normalized_name) as duplicity
                    FROM location
                    GROUP BY enum_normalized_name, iso3, nested_tree_level
                ) d ON l.enum_normalized_name = d.enum_normalized_name
                    AND l.iso3 = d.iso3
                    AND l.nested_tree_level = d.nested_tree_level
                SET l.duplicity_count = (d.duplicity - 1);
            END;
        SQL);

        $this->addSql('CALL updateLocationDuplicity');
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
