<?php

namespace Utils;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use InputType\UserCreateInputType;
use InputType\UserUpdateInputType;
use InputType\UserInitializeInputType;
use Entity\Project;
use Repository\RoleRepository;
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
    /**
     * UserService constructor.
     */
    public function __construct(private readonly EntityManagerInterface $em, private readonly ExportService $exportService, private readonly RoleHierarchyInterface $roleHierarchy, private readonly Security $security, private readonly RoleRepository $roleRepository)
    {
    }

    /**
     * @throws Exception
     */
    public function initialize(UserInitializeInputType $inputType, ?string $userDefinedSalt = null): array
    {
        $user = $this->em->getRepository(User::class)
            ->findBy(['email' => $inputType->getUsername()]);

        if ($user instanceof User) {
            throw new InvalidArgumentException('User with username ' . $inputType->getUsername());
        }

        $salt = $userDefinedSalt ?: $this->generateSalt();

        $user = new User();

        $user->setUsername($inputType->getUsername())
            ->setEmail($inputType->getUsername())
            ->setEnabled(false)
            ->setSalt($salt)
            ->setPassword('');

        $this->em->persist($user);
        $this->em->flush();

        return ['userId' => $user->getId(), 'salt' => $user->getSalt()];
    }

    public function getSalt(string $username): array
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);

        if (!$user instanceof User) {
            throw new InvalidArgumentException("User with username $username does not exists.");
        }

        return ["userId" => $user->getId(), "salt" => $user->getSalt()];
    }

    /**
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
     *
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

        /*if ($userRepository->findOneBy(['email' => $inputType->getEmail()]) instanceof User) {
            throw new InvalidArgumentException(
                'The user with email ' . $inputType->getEmail() . ' has already been added'
            );
        }*/

        $roles = $this->roleRepository->findByCodes($inputType->getRoles());

        $initializedUser->setEmail($inputType->getEmail())
            ->setEnabled(true)
            ->setRoles($roles)
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

        $roles = $this->roleRepository->findByCodes($inputType->getRoles());

        $user->setEmail($inputType->getEmail())
            ->setUsername($inputType->getUsername())
            ->setEnabled(true)
            ->setLanguage($inputType->getLanguage())
            ->setChangePassword($inputType->isChangePassword())
            ->setRoles($roles)
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
            ['username' => $this->security->getUser()->getUserIdentifier()]
        );
    }
}
