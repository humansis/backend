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

    public function load(ObjectManager $manager)
    {
        $roles = [];

        foreach (self::ROLES as $roleName) {
            $role = new Role();
            $role->setName($roleName);

            $roles[$roleName] = $role;

            $manager->persist($role);
        }

        foreach (self::PRIVILEGES as $code => $rls) {
            $privilege = new Privilege();
            $privilege->setCode($code);

            foreach ($rls as $r) {
                $privilege->addRole($roles[$r]);
            }

            $manager->persist($privilege);
        }

        $manager->flush();
    }
}
