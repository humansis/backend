<?php

declare(strict_types=1);

namespace DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Entity\Privilege;
use Entity\Role;

class RoleFixtures extends Fixture
{
    private const ROLES = [
        'ROLE_REPORTING_READ' => 'Reporting Read',
        'ROLE_REPORTING_WRITE' => 'Reporting Write',
        'ROLE_PROJECT_MANAGEMENT_READ' => 'Project Management Read',
        'ROLE_PROJECT_MANAGEMENT_WRITE' => 'Project Management Write',
        'ROLE_PROJECT_MANAGEMENT_ASSIGN' => 'Project Management Assign',
        'ROLE_BENEFICIARY_MANAGEMENT_READ' => 'Beneficiary Management Read',
        'ROLE_BENEFICIARY_MANAGEMENT_WRITE' => 'Beneficiary Management Write',
        'ROLE_USER_MANAGEMENT_READ' => 'User Management Read',
        'ROLE_USER_MANAGEMENT_WRITE' => 'User Management Write',
        'ROLE_AUTHORISE_PAYMENT' => 'Authorise Payment',
        'ROLE_USER' => 'User',
        'ROLE_DISTRIBUTION_CREATE' => 'Distribution Create',
        'ROLE_REPORTING' => 'Reporting',
        'ROLE_BENEFICIARY_MANAGEMENT' => 'Beneficiary Management',
        'ROLE_DISTRIBUTIONS_DIRECTOR' => 'Distributions Director',
        'ROLE_PROJECT_MANAGEMENT' => 'Project Management',
        'ROLE_USER_MANAGEMENT' => 'User Management',
        'ROLE_REPORTING_COUNTRY' => 'Reporting Country',
        'ROLE_VENDOR' => 'Vendor',
        'ROLE_READ_ONLY' => 'Read Only',
        'ROLE_FIELD_OFFICER' => 'Field Officer',
        'ROLE_PROJECT_OFFICER' => 'Project Officer',
        'ROLE_PROJECT_MANAGER' => 'Project Manager',
        'ROLE_COUNTRY_MANAGER' => 'Country Manager',
        'ROLE_REGIONAL_MANAGER' => 'Regional Manager',
        'ROLE_ADMIN' => 'Admin',
        'ROLE_ENUMERATOR' => 'Enumerator',
    ];
    private const PRIVILEGES = [
        'addProject' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER'],
        'editProject' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER'],
        'deleteProject' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER'],
        'viewProject' => [
            'ROLE_ADMIN',
            'ROLE_REGIONAL_MANAGER',
            'ROLE_COUNTRY_MANAGER',
            'ROLE_PROJECT_MANAGER',
            'ROLE_PROJECT_OFFICER',
            'ROLE_FIELD_OFFICER',
            'ROLE_ENUMERATOR',
        ],
        'addDistribution' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER'],
        'editDistribution' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER'],
        'deleteDistribution' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER'],
        'viewDistribution' => [
            'ROLE_ADMIN',
            'ROLE_REGIONAL_MANAGER',
            'ROLE_COUNTRY_MANAGER',
            'ROLE_PROJECT_MANAGER',
            'ROLE_PROJECT_OFFICER',
            'ROLE_FIELD_OFFICER',
            'ROLE_ENUMERATOR',
        ],
        'assignDistributionItems' => [
            'ROLE_ADMIN',
            'ROLE_COUNTRY_MANAGER',
            'ROLE_PROJECT_MANAGER',
            'ROLE_PROJECT_OFFICER',
            'ROLE_FIELD_OFFICER',
            'ROLE_ENUMERATOR',
        ],
        'addBeneficiary' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER'],
        'editBeneficiary' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER'],
        'deleteBeneficiary' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER'],
        'viewBeneficiary' => [
            'ROLE_ADMIN',
            'ROLE_REGIONAL_MANAGER',
            'ROLE_COUNTRY_MANAGER',
            'ROLE_PROJECT_MANAGER',
            'ROLE_PROJECT_OFFICER',
            'ROLE_FIELD_OFFICER',
            'ROLE_ENUMERATOR',
        ],
        'importBeneficiaries' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER', 'ROLE_PROJECT_OFFICER'],
        'exportBeneficiaries' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER'],
        'viewVouchers' => [
            'ROLE_ADMIN',
            'ROLE_REGIONAL_MANAGER',
            'ROLE_COUNTRY_MANAGER',
            'ROLE_PROJECT_MANAGER',
            'ROLE_PROJECT_OFFICER',
            'ROLE_FIELD_OFFICER',
        ],
        'exportPrintVouchers' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER'],
        'addVouchers' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER'],
        'viewVendors' => [
            'ROLE_ADMIN',
            'ROLE_REGIONAL_MANAGER',
            'ROLE_COUNTRY_MANAGER',
            'ROLE_PROJECT_MANAGER',
            'ROLE_PROJECT_OFFICER',
            'ROLE_FIELD_OFFICER',
        ],
        'addEditVendors' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER'],
        'viewDonors' => [
            'ROLE_ADMIN',
            'ROLE_REGIONAL_MANAGER',
            'ROLE_COUNTRY_MANAGER',
            'ROLE_PROJECT_MANAGER',
            'ROLE_PROJECT_OFFICER',
            'ROLE_FIELD_OFFICER',
        ],
        'addEditDonors' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER'],
        'viewProducts' => [
            'ROLE_ADMIN',
            'ROLE_REGIONAL_MANAGER',
            'ROLE_COUNTRY_MANAGER',
            'ROLE_PROJECT_MANAGER',
            'ROLE_PROJECT_OFFICER',
            'ROLE_FIELD_OFFICER',
        ],
        'addEditProducts' => ['ROLE_ADMIN', 'ROLE_PROJECT_MANAGER'],
        'countryReport' => ['ROLE_ADMIN', 'ROLE_REGIONAL_MANAGER', 'ROLE_COUNTRY_MANAGER'],
        'projectReport' => [
            'ROLE_ADMIN',
            'ROLE_REGIONAL_MANAGER',
            'ROLE_COUNTRY_MANAGER',
            'ROLE_PROJECT_MANAGER',
            'ROLE_PROJECT_OFFICER',
        ],
        'distributionReport' => [
            'ROLE_ADMIN',
            'ROLE_REGIONAL_MANAGER',
            'ROLE_COUNTRY_MANAGER',
            'ROLE_PROJECT_MANAGER',
            'ROLE_PROJECT_OFFICER',
            'ROLE_FIELD_OFFICER',
        ],
        'authoriseElectronicCashTransfer' => ['ROLE_COUNTRY_MANAGER'],
        'countrySettings' => ['ROLE_ADMIN'],
        'addEditUsers' => ['ROLE_ADMIN'],
        'adminSettings' => ['ROLE_ADMIN'],
        'moveAssistance' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER'],
    ];

    public function load(ObjectManager $manager)
    {
        $roles = [];

        foreach (self::ROLES as $code => $roleName) {
            $role = new Role();
            $role->setCode($code);
            $role->setName($roleName);

            $roles[$code] = $role;

            $manager->persist($role);
        }

        $manager->flush();

        foreach (self::PRIVILEGES as $code => $rls) {
            $privilege = new Privilege();
            $privilege->setCode($code);

            $manager->persist($privilege);

            foreach ($rls as $r) {
                $roles[$r]->getPrivileges()->add($privilege);
                $manager->persist($role);
            }
        }

        $manager->flush();
    }
}
