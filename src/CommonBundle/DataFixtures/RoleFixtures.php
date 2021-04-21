<?php
declare(strict_types=1);

namespace CommonBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Entity\Privilege;
use NewApiBundle\Entity\Role;

class RoleFixtures extends Fixture
{
    private const ROLES = [
        'ROLE_REPORTING_READ',
        'ROLE_REPORTING_WRITE',
        'ROLE_PROJECT_MANAGEMENT_READ',
        'ROLE_PROJECT_MANAGEMENT_WRITE',
        'ROLE_PROJECT_MANAGEMENT_ASSIGN',
        'ROLE_BENEFICIARY_MANAGEMENT_READ',
        'ROLE_BENEFICIARY_MANAGEMENT_WRITE',
        'ROLE_USER_MANAGEMENT_READ',
        'ROLE_USER_MANAGEMENT_WRITE',
        'ROLE_AUTHORISE_PAYMENT',
        'ROLE_USER',
        'ROLE_DISTRIBUTION_CREATE',
        'ROLE_REPORTING',
        'ROLE_BENEFICIARY_MANAGEMENT',
        'ROLE_DISTRIBUTIONS_DIRECTOR',
        'ROLE_PROJECT_MANAGEMENT',
        'ROLE_USER_MANAGEMENT',
        'ROLE_REPORTING_COUNTRY',
        'ROLE_VENDOR',
        'ROLE_READ_ONLY',
        'ROLE_FIELD_OFFICER',
        'ROLE_PROJECT_OFFICER',
        'ROLE_PROJECT_MANAGER',
        'ROLE_COUNTRY_MANAGER',
        'ROLE_REGIONAL_MANAGER',
        'ROLE_ADMIN',
        'ROLE_ENUMERATOR',
    ];

    private const PRIVILEGES = [
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

    public function load(ObjectManager $manager)
    {
        $roles = [];

        foreach (self::ROLES as $roleName) {
            $role = new Role();
            $role->setName($roleName);

            $roles[$roleName] = $role;

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
