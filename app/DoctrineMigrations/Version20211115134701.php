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
            ADD traverse_level INT DEFAULT NULL,
            ADD traverse_left INT DEFAULT NULL,
            ADD traverse_right INT DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB6D6133FE FOREIGN KEY (parent_location_id) REFERENCES location (id)');
        $this->addSql('CREATE INDEX IDX_5E9E89CB6D6133FE ON location (parent_location_id)');

        $this->addSql("UPDATE location l INNER JOIN adm1 as adm ON l.id=adm.location_id
            SET
                l.parent_location_id=NULL,
                l.name=adm.name,
                l.countryISO3=adm.countryISO3,
                l.code=adm.code,
                l.traverse_level=1
            ;

            UPDATE location l
                INNER JOIN adm2 as adm ON l.id=adm.location_id
                INNER JOIN adm1 on adm.adm1_id = adm1.id
            SET
                l.parent_location_id=adm.id,
                l.name=adm.name,
                l.countryISO3=adm1.countryISO3,
                l.code=adm.code,
                l.traverse_level=2
            ;

            UPDATE location l
                INNER JOIN adm3 as adm ON l.id=adm.location_id
                INNER JOIN adm2 on adm.adm2_id = adm2.id
                INNER JOIN adm1 on adm2.adm1_id = adm1.id
            SET
                l.parent_location_id=adm.id,
                l.name=adm.name,
                l.countryISO3=adm1.countryISO3,
                l.code=adm.code,
                l.traverse_level=3
            ;

            UPDATE location l
                INNER JOIN adm4 as adm ON l.id=adm.location_id
                INNER JOIN adm3 on adm.adm3_id = adm3.id
                INNER JOIN adm2 on adm3.adm2_id = adm2.id
                INNER JOIN adm1 on adm2.adm1_id = adm1.id
            SET
                l.parent_location_id=adm.id,
                l.name=adm.name,
                l.countryISO3=adm1.countryISO3,
                l.code=adm.code,
                l.traverse_level=4
            ;
    ");


    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE location DROP FOREIGN KEY FK_5E9E89CB6D6133FE');
        $this->addSql('DROP INDEX IDX_5E9E89CB6D6133FE ON location');
        $this->addSql('ALTER TABLE location DROP parent_location_id, DROP name, DROP countryISO3, DROP code, DROP traverse_level, DROP traverse_left, DROP traverse_right');
    }
}
