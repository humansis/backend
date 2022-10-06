<?php

namespace Utils;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use InputType\UserCreateInputType;
use InputType\UserUpdateInputType;
use InputType\UserInitializeInputType;
use Entity\Project;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Entity\User;
use Entity\UserCountry;
use Entity\UserProject;
use Repository\UserRepository;

/**
 * Class UserService
 *
 * @package Utils
 */
class UserService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var ExportService */
    private $exportService;

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
     * @param ValidatorInterface $validator
     * @param ExportService $exportService
     * @param RoleHierarchyInterface $roleHierarchy
     * @param Security $security
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        ExportService $exportService,
        RoleHierarchyInterface $roleHierarchy,
        Security $security
    ) {
        $this->em = $entityManager;
        $this->validator = $validator;
        $this->exportService = $exportService;
        $this->roleHierarchy = $roleHierarchy;
        $this->security = $security;
    }

    /**
     * @param UserInitializeInputType $inputType
     *
     * @return array
     * @throws Exception
     */
    public function initialize(UserInitializeInputType $inputType): array
    {
        $user = $this->em->getRepository(User::class)
            ->findBy(['email' => $inputType->getUsername()]);

        if ($user instanceof User) {
            throw new InvalidArgumentException('User with username '. $inputType->getUsername());
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
            throw new InvalidArgumentException("User with username $username does not exists.");
        }

        return ["userId" => $user->getId(), "salt" => $user->getSalt()];
    }

    /**
     * @param string $username
     * @param string $saltedPassword
     * @return mixed
     * @throws Exception
     */
    public function login(string $username, string $saltedPassword)
    {
        $repository = $this->em->getRepository(User::class);

        $user = $repository->findOneBy([
            'username' => $username,
            'password' => $saltedPassword,
            'enabled' => 1,
        ]);

        if (!$user instanceof User) {
            throw new Exception('Wrong password', Response::HTTP_BAD_REQUEST);
        }

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

        return $this->exportService->export($exportableTable, 'users', $type);
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
            $countries[$userProject->getProject()->getCountryIso3()] = true;
        }

        return array_keys($countries);
    }

    public function create(User $initializedUser, UserCreateInputType $inputType): User
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->em->getRepository(User::class);

        if ($userRepository->findOneBy(['email' => $inputType->getEmail()]) instanceof User) {
            throw new InvalidArgumentException(
                'The user with email ' . $inputType->getEmail() . ' has already been added'
            );
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
                $userProject->setRights(
                    $inputType->getRoles()[0]
                )//TODO edit after decision about roles and authorization will be made
                ->setUser($initializedUser)
                    ->setProject($project);

                $this->em->persist($userProject);
            }
        }

        if (!empty($inputType->getCountries())) {
            foreach ($inputType->getCountries() as $country) {
                $userCountry = new UserCountry();
                $userCountry->setUser($initializedUser)
                    ->setCountryIso3($country)
                    ->setRights(
                        $inputType->getRoles()[0]
                    );//TODO edit after decision about roles and authorization will be made

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
            throw new InvalidArgumentException('The user with email ' . $inputType->getEmail() . ' already exists');
        }

        $existingUser = $userRepository->findOneBy(['username' => $inputType->getUsername()]);
        if ($existingUser instanceof User && $existingUser->getId() !== $user->getId()) {
            throw new InvalidArgumentException(
                'The user with username ' . $inputType->getUsername() . ' already exists'
            );
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
                $userProject->setRights(
                    $inputType->getRoles()[0]
                )//TODO edit after decision about roles and authorization will be made
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
                    ->setCountryIso3($country)
                    ->setRights(
                        $inputType->getRoles()[0]
                    );//TODO edit after decision about roles and authorization will be made

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
     * @param User $user
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
     * @throws Exception
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
        return $this->em->getRepository(User::class)->findOneBy(
            ['username' => $this->security->getUser()->getUsername()]
        );
    }
}
