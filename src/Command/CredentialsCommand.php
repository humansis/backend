<?php

declare(strict_types=1);

namespace Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Component\Country\Countries;
use Entity\Role;
use Entity\User;
use Entity\UserCountry;
use Enum\RoleType;
use Repository\RoleRepository;
use Repository\UserCountryRepository;
use Repository\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class CredentialsCommand extends Command
{
    protected static $defaultName = 'app:default-credentials';

    public function __construct(
        private readonly string $account,
        private readonly string $salt,
        private readonly string $encodedPassword,
        private readonly UserRepository $userRepository,
        private readonly RoleRepository $roleRepository,
        private readonly UserCountryRepository $userCountryRepository,
        private readonly Countries $countries,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Check user for automatize testing');
        $this->setHelp('Check if user for automatize testing is created. If no this command will create it.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (empty($this->salt) || empty($this->encodedPassword)) {
            $output->writeln('<comment>Empty salt or password, skip this command.</comment>');

            return 0;
        }

        $output->writeln("Checking if user {$this->account} exists...");
        if ($this->checkIfUserExists()) {
            $output->writeln("<info>User {$this->account} already exists.</info>");

            return 0;
        }
        $output->writeln("User {$this->account} does not exist. Let's create it!");
        try {
            $this->createAccount();
            $output->writeln("<info>User {$this->account} was successfully created.</info>");

            return 0;
        } catch (Throwable $e) {
            $output->writeln(
                "<error>Error during creating user. Original exeption message: {$e->getMessage()}</error>"
            );

            return 1;
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createAccount(): void
    {
        $roles = $this->roleRepository->findByName( [RoleType::ADMIN]);
        $user = new User();
        $user->setUsername($this->account)
            ->setEnabled(true)
            ->setSalt($this->salt)
            ->setPassword($this->encodedPassword)
            ->setRoles($roles);

        $this->userRepository->save($user);
        $this->setCountries($user);
    }

    /**
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function setCountries(User $user): void
    {
        foreach ($this->countries->getAll() as $country) {
            $userCountry = new UserCountry();
            $userCountry->setCountryIso3($country->getIso3());
            $userCountry->setUser($user);
            $userCountry->setRights(RoleType::ADMIN);
            $this->userCountryRepository->save($userCountry);
        }
    }

    private function checkIfUserExists(): bool
    {
        return (bool) $this->userRepository->findOneBy(['email' => $this->account]);
    }
}
