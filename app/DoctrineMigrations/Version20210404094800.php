<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210404094800 extends AbstractMigration
{
    const PRIVILEGES = [
        'Add Project' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER'],
        'Edit Project' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER'],
        'Delete Project' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER'],
        'View Project' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER', 'ROLE_FIELD_OFFICER', 'ROLE_ENUMERATOR'],
        'Add Distribution' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER'],
        'Edit Distribution' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER'],
        'Delete Distribution' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER'],
        'View Distribution' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER', 'ROLE_FIELD_OFFICER', 'ROLE_ENUMERATOR'],
        'Assign Distribution Items' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER', 'ROLE_FIELD_OFFICER', 'ROLE_ENUMERATOR'],
        'Add Beneficiary' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER'],
        'Edit Beneficiary' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER'],
        'Delete Beneficiary' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER'],
        'View Beneficiary' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER', 'ROLE_FIELD_OFFICER', 'ROLE_ENUMERATOR'],
        'Import Beneficiaries' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER'],
        'Export Beneficiaries' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER'],
        'View Vouchers' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER', 'ROLE_FIELD_OFFICER'],
        'Export/Print Vouchers' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER'],
        'Add Vouchers' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER'],
        'View Vendors' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER', 'ROLE_FIELD_OFFICER'],
        'Add/Edit Vendors' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER'],
        'View Donors' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER', 'ROLE_FIELD_OFFICER'],
        'Add/Edit Donors' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER'],
        'View Products' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER', 'ROLE_FIELD_OFFICER'],
        'Add/Edit Products' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER'],
        'Country Report' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER'],
        'Project Report' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER'],
        'Distribution Report' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER', 'ROLE_FIELD_OFFICER'],
        'Authorise Electronic Cash Transfer' => ['ROLE_COUNTRY_MANAGER'],
        'Country Settings' => ['ROLE_ADMIN'],
        'Add/Edit Users' => ['ROLE_ADMIN'],
        'Admin Settings' => ['ROLE_ADMIN'],
    ];

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO privilege (code) VALUES ('".implode("'), ('", array_keys(self::PRIVILEGES))."')");

        foreach (self::PRIVILEGES as $privilege => $roles) {
            $this->addSql("
            INSERT INTO role_privilege (role_id, privilege_id)
                SELECT
                   role.id AS role_id,
                   privilege.id AS privilege_id
                FROM role, privilege
                WHERE privilege.code=? AND role.name IN (".implode(',', array_fill(0, count($roles), '?')).")",
                array_merge([$privilege], $roles));
        }
    }

    public function down(Schema $schema): void
    {

    }
}
