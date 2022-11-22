<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210502210413 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'CREATE VIEW view_location_recursive AS
            SELECT
                adm1.countryISO3 AS countryiso3,
                NULL AS parent_location_id,
                adm1.location_id AS location_id,
                adm1.code AS code,
                adm1.name AS name
            FROM adm1
            UNION ALL
            SELECT
                adm1.countryISO3 AS countryiso3,
                adm1.location_id AS parent_location_id,
                adm2.location_id AS location_id,
                adm2.code AS code,
                adm2.name AS name
            FROM adm2 JOIN adm1 ON adm2.adm1_id = adm1.id
            UNION ALL
            SELECT
                adm1.countryISO3 AS countryiso3,
                adm2.location_id AS parent_location_id,
                adm3.location_id AS location_id,
                adm3.code AS code,
                adm3.name AS name
            FROM adm3 JOIN adm2 ON adm3.adm2_id = adm2.id JOIN adm1 ON adm2.adm1_id = adm1.id
            UNION ALL
            SELECT
                adm1.countryISO3 AS countryiso3,
                adm3.location_id AS parent_location_id,
                adm4.location_id AS location_id,
                adm4.code AS code,
                adm4.name AS name
            FROM adm4 JOIN adm3 ON adm4.adm3_id = adm3.id JOIN adm2 ON adm3.adm2_id = adm2.id JOIN adm1 ON adm2.adm1_id = adm1.id
        '
        );
    }

    public function down(Schema $schema): void
    {
    }
}
