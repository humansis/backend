<?php
declare(strict_types=1);

namespace CommonBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
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

    public function load(ObjectManager $manager)
    {
        foreach (self::ROLES as $roleName) {
            $role = new Role();
            $role->setName($roleName);

            $manager->persist($role);
        }

        $manager->flush();
    }
}
