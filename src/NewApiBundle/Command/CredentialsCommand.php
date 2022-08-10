<?php declare(strict_types=1);

namespace NewApiBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use NewApiBundle\Component\Country\Countries;
use NewApiBundle\Entity\User;
use NewApiBundle\Entity\UserCountry;
use NewApiBundle\Enum\RoleType;
use NewApiBundle\Repository\UserCountryRepository;
use NewApiBundle\Repository\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CredentialsCommand extends Command
{
    protected static $defaultName = 'app:default-credentials';

    /**
     * @var string
     */
    private $account;

    /**
     * @var string
     */
    private $salt;

    /**
     * @var string
     */
    private $encodedPassword;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var Countries
     */
    private $countries;

    /**
     * @var UserCountryRepository
     */
    private $userCountryRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        string                 $account,
        string                 $salt,
        string                 $encodedPassword,
        UserRepository         $userRepository,
        UserCountryRepository  $userCountryRepository,
        Countries              $countries,
        EntityManagerInterface $entityManager
    ) {

        parent::__construct();
        $this->account = $account;
        $this->salt = $salt;
        $this->encodedPassword = $encodedPassword;
        $this->userRepository = $userRepository;
        $this->userCountryRepository = $userCountryRepository;
        $this->countries = $countries;
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this->setDescription('Check user for automatize testing');
        $this->setHelp('Check if user for automatize testing is created. If no this command will create it.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
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
        } catch (\Throwable $e) {
            $output->writeln("<error>Error during creating user. Original exeption message: {$e->getMessage()}</error>");

            return 1;
        }
    }

    /**
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createAccount(): void
    {
        $user = new User();
        $user->setUsername($this->account)
            ->setUsernameCanonical($this->account)
            ->setEmail($this->account)
            ->setEmailCanonical($this->account)
            ->setEnabled(true)
            ->setSalt($this->salt)
            ->setPassword($this->encodedPassword);
        $user->injectObjectManager($this->entityManager);
        $user->addRole(RoleType::ADMIN);
        $this->userRepository->save($user);
        $this->setCountries($user);
    }

    /**
     * @param User $user
     *
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function setCountries(User $user): void
    {
        foreach ($this->countries->getAll() as $country) {
            $userCountry = new UserCountry();
            $userCountry->setIso3($country->getIso3());
            $userCountry->setUser($user);
            $userCountry->setRights(RoleType::ADMIN);
            $this->userCountryRepository->save($userCountry);
        }
    }

    /**
     * @return bool
     */
    private function checkIfUserExists(): bool
    {
        return (bool) $this->userRepository->findOneBy(['email' => $this->account]);
    }
}
