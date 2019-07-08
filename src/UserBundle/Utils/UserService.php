<?php

namespace UserBundle\Utils;

use CommonBundle\Entity\Logs;
use Doctrine\ORM\EntityManagerInterface;
use ProjectBundle\Entity\Project;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UserBundle\Entity\User;
use UserBundle\Entity\UserCountry;
use UserBundle\Entity\UserProject;

/**
 * Class UserService
 * @package UserBundle\Utils
 */
class UserService
{
    private $countryList = [
        "KHM",
        "SYR",
    ];

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * UserService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface $validator
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->validator = $validator;
        $this->container = $container;
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
        $projects = $user->getUserProjects()->getValues();

        $allProjects = array();

        foreach ($projects as $project) {
            array_push($allProjects, $project->getProject()->getName());
        }

        return $allProjects;
    }

    /**
     * @param User $user
     * @param array $userData
     * @return User
     */
    public function update(User $user, array $userData)
    {
        $roles = $userData['roles'];
        if (!empty($roles)) {
            $user->setRoles($roles);
        }

        if (!empty($userData['password'])) {
            $user->setPassword($userData['password']);
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
                    $this->em->merge($userProject);
                }
            }
        }

        if (key_exists('countries', $userData)) {
            foreach ($userData['countries'] as $country) {
                $userCountry = new UserCountry();
                $userCountry->setUser($user)
                    ->setIso3($country)
                    ->setRights($roles[0]);
                $this->em->merge($userCountry);
            }
        }

        $this->em->flush();

        return $user;
    }

    /**
     * @param string $username
     * @return array
     * @throws \Exception
     */
    public function initialize(string $username)
    {
        $user = $this->em->getRepository(User::class)->findOneByUsername($username);
        if ($user instanceof User) {
            throw new \Exception("Username already used.", Response::HTTP_BAD_REQUEST);
        }
        $salt = rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '=');
        $user = new User();
        $user->setUsername($username)
            ->setUsernameCanonical($username)
            ->setEnabled(0)
            ->setEmail($username)
            ->setEmailCanonical($salt)
            ->setSalt($salt)
            ->setPassword("")
            ->setChangePassword(0);

        $this->em->persist($user);

        $this->em->flush();
        return ["user_id" => $user->getId(), "salt" => $user->getSalt()];
    }

    /**
     * @param string $username
     * @return array
     * @throws \Exception
     */
    public function getSalt(string $username)
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
     * @param $origin
     * @return mixed
     * @throws \Exception
     */
    public function login(string $username, string $saltedPassword, $origin)
    {
        $repository = $this->em->getRepository(User::class);

        $user = $repository->findOneBy([
            'username' => $username,
            'password' => $saltedPassword,
            'enabled' => 1
        ]);

        if ($user instanceof User) {
            // $countries = array();
            
            // $countryRepo = $this->em->getRepository(UserCountry::class);
            // $userCountries = $countryRepo->findBy(["user" => $user]);
            // if ($userCountries) {
            //     foreach ($userCountries as $userCountry) {
            //         array_push($countries, $userCountry->getIso3());
            //     }
            // } else {
            //     $countries = $this->countryList;
            // }
            
            // $projectRepo = $this->em->getRepository('UserBundle:UserProject');
            // $userProjects = $projectRepo->findBy(["user" => $user]);
            // if ($userProjects) {
            //     foreach ($userProjects as $userProject) {
            //         array_push($countries, $userProject->getProject()->getIso3());
            //     }
            // }
            
            // if ($origin && $user->getRoles()[0] !== "ROLE_ADMIN" && !in_array($origin, array_unique($countries))) {
            //     throw new \Exception('Unable to log in from this country (' . $origin . ')', Response::HTTP_BAD_REQUEST);
            // }
        } else {
            throw new \Exception('Wrong password', Response::HTTP_BAD_REQUEST);
        }

        return $user;
    }

    /**
     * @param array $userData
     * @return mixed
     * @throws \Exception
     */
    public function create(array $userData)
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
        
        $user->setPassword($userData['password']);

        $this->em->merge($user);

        if (key_exists('projects', $userData)) {
            foreach ($userData['projects'] as $project) {
                $project = $this->em->getRepository(Project::class)->findOneById($project);

                if ($project instanceof Project) {
                    $userProject = new UserProject();
                    $userProject->setRights($roles[0])
                        ->setUser($user)
                        ->setProject($project);
                    $this->em->merge($userProject);
                }
            }
        }

        if (key_exists('countries', $userData)) {
            foreach ($userData['countries'] as $country) {
                $userCountry = new UserCountry();
                $userCountry->setUser($user)
                    ->setIso3($country)
                    ->setRights($roles[0]);
                $this->em->merge($userCountry);
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
        $this->em->merge($user);
        $this->em->flush();

        return $user;
    }

    /**
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
                $this->em->remove($userProject);
            }
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
                ->setFrom('admin@bmstaging.info')
                ->setTo($emailConnected->getEmail())
                ->setBody(
                    $this->container->get('templating')->render(
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
                ->setFrom('admin@bmstaging.info')
                ->setTo($emailConnected->getEmail())
                ->setBody(
                    $this->container->get('templating')->render(
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

    public function updateLanguage(User $user, string $language)
    {
        $user->setLanguage($language);

        $this->em->merge($user);
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
}
