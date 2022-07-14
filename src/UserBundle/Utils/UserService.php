<?php

namespace UserBundle\Utils;

use CommonBundle\Entity\Logs;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use NewApiBundle\InputType\UserCreateInputType;
use NewApiBundle\InputType\UserUpdateInputType;
use NewApiBundle\InputType\UserInitializeInputType;
use ProjectBundle\Entity\Project;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;
use UserBundle\Entity\User;
use UserBundle\Entity\UserCountry;
use UserBundle\Entity\UserProject;
use Symfony\Component\HttpClient\HttpClient;
use UserBundle\Repository\UserRepository;

/**
 * Class UserService
 * @package UserBundle\Utils
 */
class UserService
{
    /** @var string */
    private $email;

    private $countryList = [
        "KHM",
        "SYR",
    ];

    private $environments = [
        'HID' => [
            'testing' => [
                'front_url' => 'redirect_uri=https://front-test.bmstaging.info/sso?origin=hid',
                'client_id' => 'Humsis-stag',
                'provider_url' => 'https://auth.staging.humanitarian.id'
            ],
            'demo' => [
                'front_url' => 'redirect_uri=https://demo.humansis.org/sso?origin=hid',
                'client_id' => 'Humsis-Demo',
                'provider_url' => 'https://auth.humanitarian.id'
            ],
            'prod' => [
                'front_url' => 'redirect_uri=https://front.bmstaging.info/sso?origin=hid',
                'client_id' => 'Humsis-Prod',
                'provider_url' => 'https://auth.humanitarian.id'
            ]
        ]
    ];

    protected $humanitarianSecret;
    protected $googleClient;

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
     * @var Environment
     */
    private $twig;

    /**
     * UserService constructor.
     *
     * @param string                 $googleClient
     * @param string                 $humanitarianSecret
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface     $validator
     * @param ContainerInterface     $container
     * @param RoleHierarchyInterface $roleHierarchy
     * @param Security               $security
     * @param Environment            $twig
     */
    public function __construct(
        string                 $googleClient,
        string                 $humanitarianSecret,
        EntityManagerInterface $entityManager,
        ValidatorInterface     $validator,
        ContainerInterface     $container,
        RoleHierarchyInterface $roleHierarchy,
        Security               $security,
        Environment            $twig
    ) {
        $this->googleClient = $googleClient;
        $this->humanitarianSecret = $humanitarianSecret;
        $this->em = $entityManager;
        $this->validator = $validator;
        $this->container = $container;
        $this->email = $this->container->getParameter('email');
        $this->roleHierarchy = $roleHierarchy;
        $this->security = $security;
        $this->twig = $twig;
    }

    /**
     * @param $offset
     * @param $limit
     * @return array
     */
    public function findAll($limit, $offset)
    {
        return $this->em->getRepository(User::class)->findBy([], [], $limit, $offset);
    }

    /**
     * @param User $user
     * @return array
     */
    public function findAllProjects(User $user)
    {
        $projects = $user->getProjects()->getValues();

        $allProjects = array();

        foreach ($projects as $project) {
            array_push($allProjects, $project->getProject()->getName());
        }

        return $allProjects;
    }

    /**
     * @deprecated Remove in 3.0
     *
     * @param User $user
     * @param array $userData
     * @return User
     */
    public function updateFromArray(User $user, array $userData)
    {
        $roles = $userData['roles'];
        if (!empty($roles)) {
            $user->setRoles($roles);
        }

        if (!empty($userData['password'])) {
            $user->setPassword($userData['password']);
        }
        
        if (key_exists('phone_prefix', $userData)) {
            $user->setPhonePrefix($userData['phone_prefix']);
        }
        
        if (key_exists('phone_number', $userData)) {
            $user->setPhoneNumber($userData['phone_number']);
        }

        if (key_exists('change_password', $userData)) {
            $user->setChangePassword($userData['change_password']);
        }
        if (key_exists('two_factor_authentication', $userData)) {
            $user->setTwoFactorAuthentication($userData['two_factor_authentication']);
        }
        $this->em->persist($user);
        
        $this->delete($user, false);
        
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

        $this->em->flush();
        return $user;
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
     * @deprecated Remove in 3.0
     *
     * @param string $username
     * @return array
     * @throws \Exception
     */
    public function initializeOld(string $username)
    {
        $user = $this->em->getRepository(User::class)->findOneByUsername($username);
        if ($user instanceof User) {
            throw new \Exception("Username already used.", Response::HTTP_BAD_REQUEST);
        }
        $salt = rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '=');
        $user = new User();

        $user->injectObjectManager($this->em);

        $user->setUsername($username)
            ->setUsernameCanonical($username)
            ->setEnabled(0)
            ->setEmail($username)
            ->setEmailCanonical($salt)
            ->setSalt($salt)
            ->setPassword("")
            ->setChangePassword(0);

        $user->setPhonePrefix("")
            ->setPhoneNumber(0)
            ->setTwoFactorAuthentication(0);

        $this->em->persist($user);

        $this->em->flush();
        return ["user_id" => $user->getId(), "salt" => $user->getSalt()];
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
     * @deprecated Remove in 3.0
     *
     * @param string $username
     * @return array
     * @throws \Exception
     */
    public function getSaltOld(string $username)
    {
        $validator = Validation::createValidator();
        $violations = $validator->validate($username, array(
            new Length(array('min' => 2, 'max' => 50)),
            new NotBlank(),
        ));

        if (0 !== count($violations)) {
            $errors = [];
            // there are errors, now you can show them
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            throw new \Exception(json_encode($errors), Response::HTTP_BAD_REQUEST);
        }

        $user = $this->em->getRepository(User::class)->findOneByUsername($username);

        if (!$user instanceof User) {
            throw new \Exception("This username doesn't exist", Response::HTTP_BAD_REQUEST);
        }

        return ["user_id" => $user->getId(), "salt" => $user->getSalt()];
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
     * @param User $user
     * @param $oldPassword
     * @param $newPassword
     * @return User
     * @throws \Exception
     */
    public function updatePassword(User $user, $oldPassword, $newPassword)
    {
        if ($user->getPassword() !== $oldPassword) {
            throw new \Exception("The old password doesn't match.");
        }

        $user->setPassword($newPassword)
            ->setChangePassword(0);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * @deprecated Remove in 3.0
     *
     * Delete an user and its links in the api
     *
     * @param User $user
     * @param bool $removeUser
     * @return bool
     */
    public function delete(User $user, bool $removeUser = true)
    {
        $userCountries = $this->em->getRepository(UserCountry::class)->findByUser($user);
        if (!empty($userCountries)) {
            foreach ($userCountries as $userCountry) {
                $this->em->remove($userCountry);
            }
        }
        $userProjects = $this->em->getRepository(UserProject::class)->findByUser($user);
        if (!empty($userProjects)) {
            foreach ($userProjects as $userProject) {
                if ($removeUser || !$userProject->getProject()->getArchived()) {
                    $this->em->remove($userProject);
                }
            }
        }
        $activities = $this->em->getRepository(\NewApiBundle\Entity\HouseholdActivity::class)->findBy(['author' => $user]);
        foreach ($activities as $activity) {
            $this->em->remove($activity);
        }

        if ($removeUser) {
            try {
                $this->em->remove($user);
                $this->em->flush();
            } catch (\Exception $exception) {
                return false;
            }
        }

        return true;
    }

    /**
     * @deprecated Remove in 3.0
     *
     * Delete an user and its links in the api
     *
     * @param string $username
     * @return bool
     */
    public function deleteByUsername(string $username)
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);

        $userCountries = $this->em->getRepository(UserCountry::class)->findByUser($user);
        if (!empty($userCountries)) {
            foreach ($userCountries as $userCountry) {
                $this->em->remove($userCountry);
            }
        }
        $userProjects = $this->em->getRepository(UserProject::class)->findByUser($user);
        if (!empty($userProjects)) {
            foreach ($userProjects as $userProject) {
                $this->em->remove($userProject);
            }
        }

        try {
            $this->em->remove($user);
            $this->em->flush();
        } catch (\Exception $exception) {
            return false;
        }

        return true;
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

    public function getLog(User $user, User $emailConnected)
    {
        $logs = $this->em->getRepository(Logs::class)->findBy(['idUser' => $user->getId()]);

        foreach ($logs as $log) {
            $date = $log->getDate()->format('d-m-Y H:i:s');
            $data = [$log->getUrl(), $log->getIdUser(), $log->getMailUser(), $log->getMethod(), $date, $log->getHttpStatus(), $log->getController(), $log->getRequest()];
            $this->recordLog($user->getId(), $data);
        }

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data';
        if (! is_dir($dir_var)) {
            mkdir($dir_var);
        }
        $file_record = $dir_var . '/record_log-' . $user->getId() . '.csv';


        if (is_file($file_record) && file_get_contents($file_record)) {
            $message = (new \Swift_Message('Logs of ' . $user->getUsername()))
                ->setFrom($this->email)
                ->setTo($emailConnected->getEmail())
                ->setBody(
                    $this->twig->render(
                        'Emails/logs.html.twig',
                        array(
                            'user' => $emailConnected->getUsername(),
                            'userRequested' => $user->getUsername()
                        )
                    ),
                    'text/html'
                );
            $message->attach(\Swift_Attachment::fromPath($dir_root . '/../var/data/record_log-' . $user->getId() . '.csv')->setFilename('logs-'. $user->getEmail() .'.csv'));
        } else {
            $message = (new \Swift_Message('Logs of ' . $user->getUsername()))
                ->setFrom($this->email)
                ->setTo($emailConnected->getEmail())
                ->setBody(
                    $this->twig->render(
                        'Emails/no_logs.html.twig',
                        array(
                            'user' => $emailConnected->getUsername(),
                            'userRequested' => $user->getUsername()
                        )
                    ),
                    'text/html'
                );
        }

        $this->container->get('mailer')->send($message);

        $transport = $this->container->get('mailer')->getTransport();
        $spool = $transport->getSpool();
        $spool->flushQueue($this->container->get('swiftmailer.transport.real'));

        if (is_file($file_record) && file_get_contents($file_record)) {
            unlink($file_record);
        }
    }

    /**
     * Save log record in file
     * @param int $idUser
     * @param  array $data
     * @return void
     */
    public function recordLog(int $idUser, array $data)
    {
        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data';
        if (! is_dir($dir_var)) {
            mkdir($dir_var);
        }
        $file_record = $dir_var . '/record_log-' . $idUser . '.csv';

        $fp = fopen($file_record, 'a');
        if (!file_get_contents($file_record)) {
            fputcsv($fp, array('URL', 'ID user', 'Email user', 'Method', 'Date', 'HTTP Status', 'Controller called', 'Request parameters'), ";");
        }

        fputcsv($fp, $data, ";");

        fclose($fp);
    }

    /**
     * Update user language
     * @param User $user
     * @param  string $language
     * @return void
     */
    public function updateLanguage(User $user, string $language)
    {
        $user->setLanguage($language);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * @param $offset
     * @param $limit
     * @return array
     */
    public function findWebUsers($limit, $offset)
    {
        return $this->em->getRepository(User::class)->findBy(['vendor' => null], [], $limit, $offset);
    }

     /**
     * @param $token
     * @return User
     */
    public function loginGoogle(string $token)
    {
        $client = new \Google_Client(['client_id' => $this->googleClient]);

        $payload = $client->verifyIdToken($token);
        if ($payload) {
            $email = $payload['email'];
            return $this->loginSSO($email);
        } else {
            throw new \Exception('The token could not be verified');
        }
    }

    /* *
     * @param $code
     * @param $environment
     * @return User
     */
    // public function loginLinkedIn(string $code, string $environment)
    // {
    //     $httpClient = HttpClient::create();
    //     $parameters = $this->environments['linkedIn'][$environment];
    //
    //     $response = $httpClient->request('POST', $parameters['provider_url'], [
    //         'body' => [
    //             'grant_type' => 'authorization_code',
    //             'code' => $code,
    //             'redirect_uri' => $parameters['front_url'],
    //             'client_id' => $parameters['client_id'],
    //             'client_secret' => '$this->linkedInSecret',
    //         ],
    //         'headers' => [
    //             'Content-Type' => 'application/x-www-form-urlencoded',
    //             'Accept' => '*/*',
    //         ]
    //     ]);
    //
    //     $statusCode = $response->getStatusCode();
    //     if ($statusCode === 200) {
    //         $content = $response->toArray();
    //     } else {
    //         throw new \Exception("There was a problem with the LinkedIn request: could not get token");
    //     }
    // }

    /**
     * @param $code
     * @param $environment
     * @return User
     */
    public function loginHumanitarian(string $code, string $environment)
    {
        try {
            $parameters = $this->environments['HID'][$environment];
            $token = $this->getHIDToken($code, $parameters);
            $email = $this->getHIDEmail($token, $parameters);
            return $this->loginSSO($email);
            
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function loginSSO($email) {
        $user = $this->em->getRepository(User::class)->findOneByUsername($email);
        if (!$user instanceof User) {
            // Create a random salt and password
            $salt = rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '=');
            $password = rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '=');
            $user = new User();

            $user->injectObjectManager($this->em);

            $user->setSalt($salt)
                ->setEmail($email)
                ->setEmailCanonical($email)
                ->setUsername($email)
                ->setUsernameCanonical($email)
                ->setEnabled(0)
                ->setChangePassword(false);

            $user->setPassword($password);
            $this->em->persist($user);
            $this->em->flush();
        }
        return $user;
    }

    public function getHIDToken(string $code, array $parameters) {
        $httpClient = HttpClient::create();
        
        $response = $httpClient->request('POST', $parameters['provider_url'] . '/oauth/access_token', [
            'body' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $parameters['front_url'],
                'client_id' => $parameters['client_id'],
                'client_secret' => $this->humanitarianSecret,
            ]
        ]);
        $statusCode = $response->getStatusCode();
        if ($statusCode === 200) {
            $content = $response->toArray();
            return $content['access_token'];
        } else {
            throw new \Exception("There was a problem with the HID request: could not get token"); 
        }
    }

    public function getHIDEmail(string $token, array $parameters) {
        $httpClient = HttpClient::create([ 'auth_bearer' => $token ]);
        $response = $httpClient->request('GET', $parameters['provider_url'] . '/account.json');
        $statusCode = $response->getStatusCode();

        if ($statusCode === 200) {
            $content = $response->toArray();
            return $content['email'];
        }  else {
            throw new \Exception("There was a problem with the HID request: could not get user email"); 
        }
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
