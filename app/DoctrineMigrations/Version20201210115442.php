<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201210115442 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("UPDATE national_id SET id_type='National ID' WHERE id_type='national_id'");
        $this->addSql("UPDATE national_id SET id_type='Passport' WHERE id_type='passport'");
        $this->addSql("UPDATE national_id SET id_type='Family Registration' WHERE id_type='family_registration'");
        $this->addSql("UPDATE national_id SET id_type='Birth Certificate' WHERE id_type='birth_certificate'");
        $this->addSql("UPDATE national_id SET id_type='Driver’s License' WHERE id_type='drivers_license'");
        $this->addSql("UPDATE national_id SET id_type='Camp ID' WHERE id_type='camp_id'");
        $this->addSql("UPDATE national_id SET id_type='Social Service Card' WHERE id_type='social_service_card'");
        $this->addSql("UPDATE national_id SET id_type='Other' WHERE id_type='other'");
    }

    public function down(Schema $schema): void
    {
    }
}
