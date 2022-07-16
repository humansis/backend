<?php

namespace NewApiBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use NewApiBundle\InputType\UserCreateInputType;
use NewApiBundle\InputType\UserUpdateInputType;
use NewApiBundle\InputType\UserInitializeInputType;
use NewApiBundle\Entity\Project;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UserBundle\Entity\User;
use UserBundle\Entity\UserCountry;
use UserBundle\Entity\UserProject;
use UserBundle\Repository\UserRepository;

/**
 * Class UserService
 * @package UserBundle\Utils
 */
class UserService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * @var RoleHierarchyInterface
     */
    private $roleHierarchy;

    /** @var Security $security */
    private $security;

    /**
     * UserService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface     $validator
     * @param ContainerInterface     $container
     * @param RoleHierarchyInterface $roleHierarchy
     * @param Security               $security
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface     $validator,
        ContainerInterface     $container,
        RoleHierarchyInterface $roleHierarchy,
        Security               $security
    ) {
        $this->em = $entityManager;
        $this->validator = $validator;
        $this->container = $container;
        $this->roleHierarchy = $roleHierarchy;
        $this->security = $security;
    }

    /**
     * @param UserInitializeInputType $inputType
     *
     * @return array
     * @throws \Exception
     */
    public function initialize(UserInitializeInputType $inputType): array
    {
        $user = $this->em->getRepository(User::class)
            ->findBy(['email' => $inputType->getUsername()]);

        if ($user instanceof User) {
            throw new \InvalidArgumentException('User with username '. $inputType->getUsername());
        }

        $salt = $this->generateSalt();

        $user = new User();

        $user->injectObjectManager($this->em);

        $user->setUsername($inputType->getUsername())
            ->setUsernameCanonical($inputType->getUsername())
            ->setEmail($inputType->getUsername())
            ->setEmailCanonical($inputType->getUsername())
            ->setEnabled(false)
            ->setSalt($salt)
            ->setPassword('');

        $this->em->persist($user);
        $this->em->flush();

        return ['userId' => $user->getId(), 'salt' => $user->getSalt()];
    }

    /**
     * @param string $username
     *
     * @return array
     */
    public function getSalt(string $username): array
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);

        if (!$user instanceof User) {
            throw new \InvalidArgumentException("User with username $username does not exists.");
        }

        return ["userId" => $user->getId(), "salt" => $user->getSalt()];
    }

    /**
     * @param string $username
     * @param string $saltedPassword
     * @return mixed
     * @throws \Exception
     */
    public function login(string $username, string $saltedPassword)
    {
        $repository = $this->em->getRepository(User::class);

        $user = $repository->findOneBy([
            'username' => $username,
            'password' => $saltedPassword,
            'enabled' => 1
        ]);

        if (!$user instanceof User) {
            throw new \Exception('Wrong password', Response::HTTP_BAD_REQUEST);
        }

        return $user;
    }

    /**
     * @deprecated Remove in 3.0
     *
     * @param array $userData
     * @return mixed
     * @throws \Exception
     */
    public function createFromArray(array $userData)
    {
        $roles = $userData['roles'];

        if (!isset($roles) || empty($roles)) {
            throw new \Exception("Rights can not be empty");
        }

        $user = $this->em->getRepository(User::class)->findOneByUsername($userData['username']);

        if (!$user instanceof User) {
            throw new \Exception("The user with username " . $userData['username'] . " has been not preconfigured. You need to ask 
            the salt for this username beforehand.");
        } elseif ($user->isEnabled()) {
            throw new \Exception("The user with username " . $userData['username'] . " has already been added");
        }

        $user->setSalt($userData['salt'])
            ->setEmail($user->getUsername())
            ->setEmailCanonical($user->getUsername())
            ->setUsername($user->getUsername())
            ->setUsernameCanonical($user->getUsername())
            ->setEnabled(1)
            ->setRoles($roles)
            ->setChangePassword($userData['change_password']);
        
        $user->setPhonePrefix($userData['phone_prefix'])
            ->setPhoneNumber($userData['phone_number'])
            ->setTwoFactorAuthentication($userData['two_factor_authentication']);
        
        $user->setPassword($userData['password']);

        $this->em->persist($user);

        if (key_exists('projects', $userData)) {
            foreach ($userData['projects'] as $project) {
                $project = $this->em->getRepository(Project::class)->findOneById($project);

                if ($project instanceof Project) {
                    $userProject = new UserProject();
                    $userProject->setRights($roles[0])
                        ->setUser($user)
                        ->setProject($project);
                    $this->em->persist($userProject);
                }
            }
        }

        if (key_exists('countries', $userData)) {
            foreach ($userData['countries'] as $country) {
                $userCountry = new UserCountry();
                $userCountry->setUser($user)
                    ->setIso3($country)
                    ->setRights($roles[0]);
                $this->em->persist($userCountry);
            }
        }

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[] = $error->getMessage();
            }
            return $errorsArray;
        }

        $this->em->flush();
        return $user;
    }

    /**
     * Export all users in a CSV file
     * @param string $type
     * @return mixed
     */
    public function exportToCsv(string $type)
    {
        $exportableTable = $this->em->getRepository(User::class)->findAll();

        return $this->container->get('export_csv_service')->export($exportableTable, 'users', $type);
    }

    public function getCountries(User $user): array
    {
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return ['KHM', 'SYR', 'UKR', "ETH", "MNG", "ARM", "ZMB"];
        }

        $countries = [];
        foreach ($user->getCountries() as $country) {
            $countries[$country->getIso3()] = true;
        }

        foreach ($user->getProjects() as $userProject) {
            /** @var UserProject $userProject */
            $countries[$userProject->getProject()->getIso3()] = true;
        }

        return array_keys($countries);
    }

    public function create(User $initializedUser, UserCreateInputType $inputType): User
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->em->getRepository(User::class);

        if ($userRepository->findOneBy(['email' => $inputType->getEmail()]) instanceof User) {
            throw new InvalidArgumentException('The user with email '.$inputType->getEmail().' has already been added');
        }

        $initializedUser->setEmail($inputType->getEmail())
            ->setEmailCanonical($inputType->getEmail())
            ->setEnabled(true)
            ->setRoles($inputType->getRoles())
            ->setLanguage($inputType->getLanguage())
            ->setChangePassword($inputType->isChangePassword())
            ->setPhonePrefix($inputType->getPhonePrefix())
            ->setPhoneNumber($inputType->getPhoneNumber() ? (int) $inputType->getPhoneNumber() : null)
            ->setPassword($inputType->getPassword());

        if (!empty($inputType->getProjectIds())) {
            foreach ($inputType->getProjectIds() as $projectId) {
                $project = $this->em->getRepository(Project::class)->find($projectId);

                if (!$project instanceof Project) {
                    throw new NotFoundHttpException("Project with id $projectId not found");
                }

                $userProject = new UserProject();
                $userProject->setRights($inputType->getRoles()[0])//TODO edit after decision about roles and authorization will be made
                ->setUser($initializedUser)
                    ->setProject($project);

                $this->em->persist($userProject);
            }
        }

        if (!empty($inputType->getCountries())) {
            foreach ($inputType->getCountries() as $country) {
                $userCountry = new UserCountry();
                $userCountry->setUser($initializedUser)
                    ->setIso3($country)
                    ->setRights($inputType->getRoles()[0]);//TODO edit after decision about roles and authorization will be made

                $this->em->persist($userCountry);
            }
        }

        $this->em->persist($initializedUser);
        $this->em->flush();

        return $initializedUser;
    }

    public function update(User $user, UserUpdateInputType $inputType): User
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->em->getRepository(User::class);

        $existingUser = $userRepository->findOneBy(['email' => $inputType->getEmail()]);
        if ($existingUser instanceof User && $existingUser->getId() !== $user->getId()) {
            throw new InvalidArgumentException('The user with email '.$inputType->getEmail().' already exists');
        }

        $existingUser = $userRepository->findOneBy(['username' => $inputType->getUsername()]);
        if ($existingUser instanceof User && $existingUser->getId() !== $user->getId()) {
            throw new InvalidArgumentException('The user with username '.$inputType->getUsername().' already exists');
        }

        $user->setEmail($inputType->getEmail())
            ->setEmailCanonical($inputType->getEmail())
            ->setUsername($inputType->getUsername())
            ->setUsernameCanonical($inputType->getUsername())
            ->setEnabled(true)
            ->setLanguage($inputType->getLanguage())
            ->setChangePassword($inputType->isChangePassword())
            ->setRoles($inputType->getRoles())
            ->setPhonePrefix($inputType->getPhonePrefix())
            ->setPhoneNumber($inputType->getPhoneNumber() ? (int) $inputType->getPhoneNumber() : null);

        if (null !== $inputType->getPassword()) {
            $user->setPassword($inputType->getPassword());
        }

        /** @var UserProject $userProject */
        foreach ($user->getProjects() as $userProject) {
            $this->em->remove($userProject);
        }
        $user->getProjects()->clear();

        if (!empty($inputType->getProjectIds())) {
            foreach ($inputType->getProjectIds() as $projectId) {
                $project = $this->em->getRepository(Project::class)->find($projectId);

                if (!$project instanceof Project) {
                    throw new NotFoundHttpException("Project with id $projectId not found");
                }

                $userProject = new UserProject();
                $userProject->setRights($inputType->getRoles()[0])//TODO edit after decision about roles and authorization will be made
                ->setUser($user)
                    ->setProject($project);

                $this->em->persist($userProject);
            }
        }

        /** @var UserCountry $userCountry */
        foreach ($user->getCountries() as $userCountry) {
            $this->em->remove($userCountry);
        }
        $user->getCountries()->clear();

        if (!empty($inputType->getCountries())) {
            foreach ($inputType->getCountries() as $country) {
                $userCountry = new UserCountry();
                $userCountry->setUser($user)
                    ->setIso3($country)
                    ->setRights($inputType->getRoles()[0]);//TODO edit after decision about roles and authorization will be made

                $this->em->persist($userCountry);
            }
        }

        $this->em->flush();

        return $user;
    }

    public function remove(User $user): void
    {
        $this->em->remove($user);
        $this->em->flush();
    }

    /**
     * @param User   $user
     * @param string $role
     *
     * @return bool
     */
    public function isGranted(User $user, string $role): bool
    {
        foreach ($this->roleHierarchy->getReachableRoleNames($user->getRoles()) as $reachableRole) {
            if ($reachableRole === $role) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function generateSalt()
    {
        return rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '=');
    }

    /**
     * @return object|User|null
     */
    public function getCurrentUser()
    {
        return $this->em->getRepository(User::class)->findOneBy(['username' => $this->security->getUser()->getUsername()]);
    }
}
