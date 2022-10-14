<?php

namespace DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Exception;
use Enum\RoleType;
use Entity\Project;
use InputType\UserCreateInputType;
use InputType\UserInitializeInputType;
use Repository\UserRepository;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tests\BMSServiceTestCase;
use Entity\User;
use Entity\UserCountry;
use Doctrine\Persistence\ObjectManager;
use Entity\UserProject;
use Utils\UserService;

/**
 * @see VendorFixtures for check vendor username(s) is same
 */
class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public const REF_VENDOR_KHM = 'vendor@example.org';
    public const REF_VENDOR_SYR = 'vendor.syr@example.org';

    /** @var Kernel $kernel */
    private $kernel;

    /** @var UserService  */
    private $userService;

    /** @var ValidatorInterface  */
    private $validator;

    /** @var UserRepository */
    private $repository;

    public function __construct(
        Kernel $kernel,
        UserService $userService,
        ValidatorInterface $validator,
        UserRepository $repository
    ) {
        $this->kernel = $kernel;
        $this->userService = $userService;
        $this->validator = $validator;
        $this->repository = $repository;
    }

    private $defaultCountries = ["KHM", "SYR", "UKR", "ETH", "MNG", "ARM", "ZMB"];

    // generated by:
    // bin/console security:encode-password --no-interaction PASSWORD \Entity\\User
    private $allCountryUsers = [
        'regional.manager' => [
            'email' => 'regional.manager@example.org',
            'passwd' => 'Zm030mFWASHbXmC5pnaKzPAaWb5JfsoRHiqEMdxn5q5sGcHA77Yb89RTE32n+5aTGeOLO23KAFemVaNtTosbQg==',
            'salt' => 'Rneuh6LQREX+6LjbcNqifnP59x/h46vphP9jHyNv',
            'roles' => 'ROLE_REGIONAL_MANAGER',
        ],
        'admin' => [
            'email' => 'admin@example.org',
            'passwd' => 'WvbKrt5YeWcDtzWg4C8uUW9a3pmHi6SkXvnvvCisIbNQqUVtaTm8Myv/Hst1IEUDv3NtrqyUDC4BygbjQ/zePw==',
            'salt' => 'fhn91jwIbBnFAgZjQZA3mE4XUrjYzWfOoZDcjt/9',
            'roles' => 'ROLE_ADMIN',
        ],
        'test' => [
            'email' => 'test@example.org',
            'passwd' => '',
            'salt' => '',
            'roles' => 'ROLE_ADMIN',
        ],
    ];

    private $singleCountryUsers = [
        [
            'email' => 'country.manager@example.org',
            'passwd' => 'Pj+YRYibCUOzk4EgtilEJo6wYIUElVBdfIonbovm/6ADJjjCzunXHNMSd42z+TIt/nlhipHgeUTKewx778bDfw==',
            'salt' => 'IqYj6MfmudwB7Q5nMssApAoeAWumCTcSkvL0FnBL',
            'roles' => 'ROLE_COUNTRY_MANAGER',
        ],
        [
            'email' => 'vendor@example.org',
            'passwd' => 'O06QeWJdIK+RGkP65jnCAHtnuShmhZ8YGCAt4kqYcgZZgV2UgcqPfTD4T+/Cut8vibfiBGKJGnNgDfy5hTA0iQ==',
            'salt' => 'xZOz73DpUASslYiAUHS13Ca0289F1Vg0dDWtqxiB',
            'roles' => 'ROLE_VENDOR',
        ],
        [
            'email' => 'field.officer@example.org',
            'passwd' => 'ejsXHQZLKb+t8w4TUTC/d38dAFeo3uoB2muuMRA6ahdV8U5cAcIHh37EuOUEsMa8ZgXx0efbjIoG76DBhLRHvA==',
            'salt' => 'DHZrXXwviwTt0dUkwD/fwweGpMHN1ADw3Pj0LaxD',
            'roles' => 'ROLE_FIELD_OFFICER',
        ],
        [
            'email' => 'project.officer@example.org',
            'passwd' => 'Sikp3+vafpYEmDpt++GL6topqY3ScD5kNAY846x1RW9t7HXH6EtCMU0VP7bqzsENeZUcWtTus6kUgP14JV/TeA==',
            'salt' => 'fouJULRefDDt0fflafMw9giQxstmZ4No7K6jHu2x',
            'roles' => 'ROLE_PROJECT_OFFICER',
        ],
        [
            'email' => 'project.manager@example.org',
            'passwd' => 'TI2S81KRXUNHLL5DQGUYCvYMJmyqhR1QE8FEmKje6mETRxkrOxR5WptaSrTa4UDo9zCoCvvlxtPdipkKzES0VA==',
            'salt' => 'nu0TeRJJhkaAAYrJLCIwIktObO4xtmVtDZVGLJrj',
            'roles' => 'ROLE_PROJECT_MANAGER',
        ],
        [
            'email' => 'enumerator@example.org',
            'passwd' => 'WcHMqN9bbZN6d45R68GaghNRSmkCk7D0h42SsnYrSySMHiV2OBIyQ/tdpKocbQhW1GEOYyhEutAgiHxY2/DPzg==',
            'salt' => '6fkDJPa0eXKNkL6Ygz62jCSluL0p0lIocJKV4lBL',
            'roles' => 'ROLE_ENUMERATOR',
        ],
        [
            'email' => BMSServiceTestCase::USER_TESTER,
            'passwd' => 'LU4oaFBtfra56OnVPLLL5JuqRVKBcIlfk3dh1I/x3++yiYg/PylXdhcXNkbv8AUQeq0s3WETYA9d9/ItapaOBg==',
            'salt' => 'LZEDazS3/5yJWLfFLnzy9udyHS0rlbZvWg8Ropns',
            'roles' => 'ROLE_ADMIN',
        ],
    ];

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     * @throws Exception
     */
    public function load(ObjectManager $manager)
    {
        if ($this->kernel->getEnvironment() === "prod") {
            echo __CLASS__ . " can't be running at production\n";

            return;
        }

        foreach ($this->allCountryUsers as $name => $userData) {
            $user = $this->makeUser($manager, $userData, $this->defaultCountries);
            $this->setReference('user_' . $name, $user);
        }

        foreach ($this->singleCountryUsers as $index => $userData) {
            // user without country use first one
            $this->makeUser($manager, $userData, [$this->defaultCountries[0]]);

            foreach ($this->defaultCountries as $iso3) {
                $countrySpecificUserData = $userData;
                $countrySpecificUserData['email'] = str_replace('@', '.' . strtolower($iso3) . '@', $userData['email']);
                $this->makeUser($manager, $countrySpecificUserData, [$iso3]);
            }
        }
    }

    /**
     * @param ObjectManager $manager
     * @param $userData
     * @param array $countries
     * @return void
     */
    private function makeUser(ObjectManager $manager, $userData, array $countries): User
    {
        $instance = $manager->getRepository(User::class)->findOneByUsername($userData['email']);
        if ($instance instanceof User) {
            echo "User {$instance->getUsername()} already exists. Ommit creation.\n";
        } else {
            $instance = $this->saveDataAsUser($userData);
        }

        $this->makeAccessRights($manager, $instance, $countries);
        if (!$this->userService->isGranted($instance, RoleType::ADMIN)) {
            $this->makeProjectConnections($manager, $instance, $countries);
        }
        $manager->persist($instance);
        $manager->flush();

        if (self::REF_VENDOR_KHM === $instance->getUsername()) {
            $this->setReference(self::REF_VENDOR_KHM, $instance);
        } elseif (self::REF_VENDOR_SYR === $instance->getUsername()) {
            $this->setReference(self::REF_VENDOR_SYR, $instance);
        }

        return $instance;
    }

    private function saveDataAsUser(array $userData): User
    {
        $userInitializeInputType = new UserInitializeInputType();
        $userInitializeInputType->setUsername($userData['email']);
        $this->validator->validate($userInitializeInputType);

        $initializedUser = $this->userService->initialize($userInitializeInputType, $userData['salt']);

        /** @var User $user */
        $user = $this->repository->find($initializedUser['userId']);

        $userCreateInputType = new UserCreateInputType();
        $userCreateInputType->setEmail($userData['email']);
        $userCreateInputType->setPassword($userData['passwd']);
        $userCreateInputType->setRoles([$userData['roles']]);
        $userCreateInputType->setChangePassword(false);
        $this->validator->validate($userCreateInputType);

        return $this->userService->create($user, $userCreateInputType);
    }

    private function makeAccessRights(ObjectManager $manager, User $instance, array $countryCodes)
    {
        foreach ($countryCodes as $country) {
            $currentAccess = $manager->getRepository(UserCountry::class)->findOneBy([
                'user' => $instance,
                'countryIso3' => $country,
            ], ['id' => 'asc']);

            if ($currentAccess === null) {
                $userCountry = new UserCountry();
                $userCountry->setUser($instance)
                    ->setCountryIso3($country)
                    ->setRights($instance->getRoles()[0]);
                $instance->addCountry($userCountry);
            } else {
                echo "User {$instance->getUsername()} access to {$country} already exists. Ommit creation.\n";
                $currentAccess->setRights($instance->getRoles()[0]);
            }
        }
    }

    /**
     * @param ObjectManager $manager
     * @param User $user
     * @param array $countries
     */
    private function makeProjectConnections(ObjectManager $manager, User $user, array $countries): void
    {
        $countryProjects = $manager->getRepository(Project::class)->findBy([
            'countryIso3' => $countries,
        ], ['id' => 'asc']);
        foreach ($countryProjects as $countryProject) {
            $userProject = $manager->getRepository(UserProject::class)->findOneBy([
                'user' => $user,
                'project' => $countryProject,
            ], ['id' => 'asc']);
            if ($userProject instanceof UserProject) {
                echo "User {$user->getUsername()} access to {$countryProject->getName()} project already exists. Ommit creation.\n";
                continue;
            }

            $userProject = new UserProject();
            $userProject->setProject($countryProject);
            $userProject->setUser($user);
            $userProject->setRights($user->getRoles()[0]);
            $manager->persist($userProject);
        }
        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            ProjectFixtures::class,
            RoleFixtures::class,
        ];
    }
}
