<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210404094800 extends AbstractMigration
{
    public const PRIVILEGES = [
        'addProject' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER'],
        'editProject' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER'],
        'deleteProject' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER'],
        'viewProject' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER', 'ROLE_FIELD_OFFICER', 'ROLE_ENUMERATOR'],
        'addDistribution' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER'],
        'editDistribution' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER'],
        'deleteDistribution' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER'],
        'viewDistribution' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER', 'ROLE_FIELD_OFFICER', 'ROLE_ENUMERATOR'],
        'assignDistributionItems' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER', 'ROLE_FIELD_OFFICER', 'ROLE_ENUMERATOR'],
        'addBeneficiary' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER'],
        'editBeneficiary' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER'],
        'deleteBeneficiary' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER'],
        'viewBeneficiary' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER', 'ROLE_FIELD_OFFICER', 'ROLE_ENUMERATOR'],
        'importBeneficiaries' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER'],
        'exportBeneficiaries' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER'],
        'viewVouchers' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER', 'ROLE_FIELD_OFFICER'],
        'exportPrintVouchers' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER'],
        'addVouchers' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER'],
        'viewVendors' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER', 'ROLE_FIELD_OFFICER'],
        'addEditVendors' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER'],
        'viewDonors' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER', 'ROLE_FIELD_OFFICER'],
        'addEditDonors' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER'],
        'viewProducts' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER', 'ROLE_FIELD_OFFICER'],
        'addEditProducts' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER'],
        'countryReport' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER'],
        'projectReport' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER'],
        'distributionReport' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER', 'ROLE_FIELD_OFFICER'],
        'authoriseElectronicCashTransfer' => ['ROLE_COUNTRY_MANAGER'],
        'countrySettings' => ['ROLE_ADMIN'],
        'addEditUsers' => ['ROLE_ADMIN'],
        'adminSettings' => ['ROLE_ADMIN'],
    ];

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO privilege (code) VALUES ('" . implode("'), ('", array_keys(self::PRIVILEGES)) . "')");

        foreach (self::PRIVILEGES as $privilege => $roles) {
            $this->addSql(
                "
            INSERT INTO role_privilege (role_id, privilege_id)
                SELECT
                   role.id AS role_id,
                   privilege.id AS privilege_id
                FROM role, privilege
                WHERE privilege.code=? AND role.name IN (" . implode(',', array_fill(0, count($roles), '?')) . ")",
                array_merge([$privilege], $roles)
            );
        }
    }

    public function down(Schema $schema): void
    {
    }
}
