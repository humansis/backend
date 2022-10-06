<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220428100413 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE adm1 SET adm1.countryISO3 = 'UA1' WHERE adm1.countryISO3 = 'UKR';");
        $this->addSql("UPDATE booklet b SET b.country_iso3 = 'UA1' WHERE b.country_iso3 = 'UKR';");
        $this->addSql("UPDATE country_specific cs SET cs.country_iso3 = 'UA1' WHERE cs.country_iso3 = 'UKR';");
        $this->addSql("UPDATE import i SET i.iso3 = 'UA1' WHERE i.iso3 = 'UKR';");
        $this->addSql("UPDATE location l SET l.countryISO3 = 'UA1' WHERE l.countryISO3 = 'UKR';");
        $this->addSql("UPDATE product p SET p.countryISO3 = 'UA1' WHERE p.countryISO3 = 'UKR';");
        $this->addSql("UPDATE project p SET p.iso3 = 'UA1' WHERE p.iso3 = 'UKR';");
        $this->addSql("UPDATE reporting_country rc SET rc.country = 'UA1' WHERE rc.country = 'UKR';");
        $this->addSql("UPDATE service s SET s.country = 'UA1' WHERE s.country = 'UKR';");
        $this->addSql("UPDATE user_country us SET us.iso3 = 'UA1' WHERE us.iso3 = 'UKR';");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'This migration could not be migrated back');
    }
}
