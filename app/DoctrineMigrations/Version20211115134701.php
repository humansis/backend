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
