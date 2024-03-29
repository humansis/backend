<?php

declare(strict_types=1);

namespace Command;

use Component\Country\Countries;
use Entity\User;
use Entity\UserCountry;
use Enum\RoleType;
use Repository\RoleRepository;
use Repository\UserCountryRepository;
use Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(name: 'app:default-credentials')]
class CredentialsCommand extends Command
{
    public function __construct(
        private readonly string $account,
        private readonly string $salt,
        private readonly string $encodedPassword,
        private readonly UserRepository $userRepository,
        private readonly RoleRepository $roleRepository,
        private readonly UserCountryRepository $userCountryRepository,
        private readonly Countries $countries,
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

    private function createAccount(): void
    {
        $roles = $this->roleRepository->findByCodes([RoleType::ADMIN]);
        $user = new User(
            username: $this->account,
            email: $this->account,
            password: $this->encodedPassword,
            enabled: true,
            salt: $this->salt,
        );
        $user->setRoles($roles);

        $this->userRepository->save($user);
        $this->setCountries($user);
    }

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
