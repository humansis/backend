<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211115134701 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE location
            ADD parent_location_id INT DEFAULT NULL,
            ADD name VARCHAR(255) DEFAULT NULL,
            ADD countryISO3 VARCHAR(3) DEFAULT NULL,
            ADD code VARCHAR(255) DEFAULT NULL,
            ADD nested_tree_level INT DEFAULT NULL,
            ADD nested_tree_left INT DEFAULT NULL,
            ADD nested_tree_right INT DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB6D6133FE FOREIGN KEY (parent_location_id) REFERENCES location (id)');
        $this->addSql('CREATE INDEX IDX_5E9E89CB6D6133FE ON location (parent_location_id)');

        $this->addSql("
            UPDATE location l INNER JOIN adm1 as adm ON l.id=adm.location_id
            SET
                l.parent_location_id=NULL,
                l.name=adm.name,
                l.countryISO3=adm.countryISO3,
                l.code=adm.code,
                l.nested_tree_level=1
            ;");

        $this->addSql("
            UPDATE location l
                INNER JOIN adm2 as adm ON l.id=adm.location_id
                INNER JOIN adm1 on adm.adm1_id = adm1.id
            SET
                l.parent_location_id=adm1.location_id,
                l.name=adm.name,
                l.countryISO3=adm1.countryISO3,
                l.code=adm.code,
                l.nested_tree_level=2
            ;");

        $this->addSql("
            UPDATE location l
                INNER JOIN adm3 as adm ON l.id=adm.location_id
                INNER JOIN adm2 on adm.adm2_id = adm2.id
                INNER JOIN adm1 on adm2.adm1_id = adm1.id
            SET
                l.parent_location_id=adm2.location_id,
                l.name=adm.name,
                l.countryISO3=adm1.countryISO3,
                l.code=adm.code,
                l.nested_tree_level=3
            ;");

        $this->addSql("
            UPDATE location l
                INNER JOIN adm4 as adm ON l.id=adm.location_id
                INNER JOIN adm3 on adm.adm3_id = adm3.id
                INNER JOIN adm2 on adm3.adm2_id = adm2.id
                INNER JOIN adm1 on adm2.adm1_id = adm1.id
            SET
                l.parent_location_id=adm3.location_id,
                l.name=adm.name,
                l.countryISO3=adm1.countryISO3,
                l.code=adm.code,
                l.nested_tree_level=4
            ;
        ");

        // recount nested set
        $this->addSql('DROP PROCEDURE IF EXISTS recalculateLocationNestedSet;');
        $this->addSql('DROP PROCEDURE IF EXISTS recalculateLocationNestedSet_recurse;');
        $this->addSql('
            CREATE PROCEDURE recalculateLocationNestedSet()
            BEGIN
                SET @left_value = 1;

                -- now do recusion
                CALL recalculateLocationNestedSet_recurse(NULL, NULL);
            END;

            CREATE PROCEDURE recalculateLocationNestedSet_recurse(root INTEGER, parent INTEGER)
            BEGIN
                DECLARE done             INTEGER DEFAULT 0;
                DECLARE node             INTEGER;
                DECLARE roots     CURSOR FOR SELECT id FROM location WHERE location.parent_location_id IS NULL  ORDER BY id;
                DECLARE children  CURSOR FOR SELECT id FROM location WHERE location.parent_location_id = parent ORDER BY id;
                DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

                -- MySQL setting - allow up to 10 stored procedure recursions. Default is 0.
                SET max_sp_recursion_depth = 10;

                -- this is bypassed on first run
                IF parent IS NOT NULL THEN
                    UPDATE location SET location.nested_tree_left = @left_value WHERE id = parent;
                    SET @left_value = @left_value + 1;
                END IF;

                OPEN roots;
                OPEN children;

                -- for 1st run, and for root nodes
                IF parent IS NULL THEN
                    FETCH roots INTO node;
                    REPEAT
                        IF node IS NOT NULL THEN
                            CALL recalculateLocationNestedSet_recurse(node, node);
                            SET @left_value = @left_value + 1;
                        END IF;
                        FETCH roots INTO node;
                    UNTIL done END REPEAT;
                ELSE
                    FETCH children INTO node;
                    REPEAT
                        IF node IS NOT NULL THEN
                            CALL recalculateLocationNestedSet_recurse(root, node);
                            SET @left_value = @left_value + 1;
                        END IF;
                        FETCH children INTO node;
                    UNTIL done END REPEAT;
                END IF;
                UPDATE location set location.nested_tree_right = @left_value where id = parent;

                CLOSE roots;
                CLOSE children;
            END;
        ');
        $this->addSql('CALL recalculateLocationNestedSet');

        $this->addSql('CREATE INDEX search_name ON location (name)');
        $this->addSql('CREATE INDEX search_country_name ON location (countryISO3, name)');
        $this->addSql('CREATE INDEX search_subtree ON location (countryISO3, nested_tree_level, nested_tree_left, nested_tree_right)');
        $this->addSql('CREATE INDEX search_superpath ON location (nested_tree_level, nested_tree_left, nested_tree_right)');
        $this->addSql('CREATE INDEX search_level ON location (countryISO3, nested_tree_left)');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX search_name ON location');
        $this->addSql('DROP INDEX search_country_name ON location');
        $this->addSql('DROP INDEX search_subtree ON location');
        $this->addSql('DROP INDEX search_superpath ON location');
        $this->addSql('DROP INDEX search_level ON location');
        $this->addSql('ALTER TABLE location DROP FOREIGN KEY FK_5E9E89CB6D6133FE');
        $this->addSql('DROP INDEX IDX_5E9E89CB6D6133FE ON location');
        $this->addSql('ALTER TABLE location DROP parent_location_id, DROP name, DROP countryISO3, DROP code, DROP nested_tree_level, DROP nested_tree_left, DROP nested_tree_right');
    }
}
