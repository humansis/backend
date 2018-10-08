<?php

namespace UserBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UserBundle\Entity\User;
use UserBundle\Entity\UserCountry;
use UserBundle\Entity\UserProject;
use Psr\Container\ContainerInterface;

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
     * @param  string $username
     * @return User
     */
    public function getUserByUsername(string $username)
    {
        return $this->em->getRepository(User::class)->findOneByUsername($username);
    }

    /**
     * @param User $user
     * @param array $userData
     * @return User
     */
    public function update(User $user, array $userData)
    {
        $roles = $userData['rights'];
        $user->setRoles([]);
        $user->addRole($roles);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
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

        if (0 !== count($violations))
        {
            $errors = [];
            // there are errors, now you can show them
            foreach ($violations as $violation)
            {
                $errors[] = $violation->getMessage();
            }
            throw new \Exception(json_encode($errors), Response::HTTP_BAD_REQUEST);
        }

        $user = $this->em->getRepository(User::class)->findOneByUsername($username);

        if (!$user instanceof User)
        {
            $salt = rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '=');
            $user = new User();
            $user->setUsername($username)
                ->setUsernameCanonical($username)
                ->setEnabled(0)
                ->setEmail($username)
                ->setEmailCanonical($salt)
                ->setSalt($salt)
                ->setPassword("");


            $this->em->persist($user);

            $this->em->flush();
        }

        return ["user_id" => $user->getId(), "salt" => $user->getSalt()];
    }

    /**
     * @param string $username
     * @param string $saltedPassword
     * @param bool $isCreation
     * @return array
     * @throws \Exception
     */
    public function login(string $username, string $saltedPassword, bool $isCreation)
    {
        $repository = $this->em->getRepository('UserBundle:User');

        $user = $repository->findOneBy([
            'username' => $username,
            'password' => $saltedPassword,
            'enabled' => 1
        ]);

        if ($user instanceOf User)
        {
            $data = [
                'at' => time(),
                'connected' => true,
                'user_id' => $user->getId(),
                'salted_password' => $user->getPassword(),
                'username' => $user->getUsername()
            ];

        }
        elseif ($isCreation)
        {
            $user = $repository->findOneBy([
                'username' => $username
            ]);
            if ($user instanceOf User)
            {
                $user->setPassword($saltedPassword)
                ->setEnabled(1);
                $this->em->persist($user);
                $this->em->flush();

                $data = [
                    'at' => time(),
                    'registered' => true,
                    'username' => $user->getUsername(),
                    'salted_password' => $user->getPassword()
                ];
            }
            else
            {
                throw new \Exception('Bad credentials (username: ' . $username . ')', Response::HTTP_BAD_REQUEST);
            }

        }
        else
        {
            throw new \Exception('Bad credentials (username: ' . $username . ')', Response::HTTP_BAD_REQUEST);
        }

        return $data;

    }

    /**
     * @param User $user
     * @param array $userData
     * @return mixed
     * @throws \Exception
     */
    public function create(User $user, array $userData)
    {
        $role = $userData['rights'];

        $userSaved = $this->em->getRepository(User::class)->findOneByUsername($user->getUsername());
        if (!$userSaved instanceof User)
            throw new \Exception("The user with username {$user->getUsername()} has been not preconfigured. You need to ask 
            the salt for this username before.");
        elseif ($userSaved->isEnabled())
            throw new \Exception("The user with username {$user->getUsername()} has already been added");


        $user->setId($userSaved->getId())
            ->setSalt($userSaved->getSalt())
            ->setEmail($user->getUsername())
            ->setEmailCanonical($user->getUsername())
            ->setEnabled(1)
            ->setRoles([$role]);

        //$user->setPassword($this->encoderFactory->getEncoder($user)->encodePassword('tester', $salt));

        $this->em->merge($user);

        if(key_exists('projects', $userData))
            foreach ($userData['projects'] as $project){
                $project = $this->em->getRepository(Project::class)->find($project);
                if($project instanceof Project){
                    $userProject = new UserProject();
                    $userProject->setRights(1)
                        ->setUser($user)
                        ->setProject($project);
                    $this->em->merge($userProject);
                }

            }

        if(key_exists('country', $userData)){
            $userCountry = new UserCountry();
            $userCountry->setUser($user)
                ->setIso3($userData['country'])
                ->setRights(1);
            $this->em->merge($userCountry);
        }

        $errors = $this->validator->validate($user);
        if (count($errors) > 0)
        {
            $errorsArray = [];
            foreach ($errors as $error)
            {
                $errorsArray[] = $error->getMessage();
            }
            return $errorsArray;
        }

        $this->em->flush();
        return json_encode($user);
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
        if ($user->getPassword() !== $oldPassword)
            throw new \Exception("The old password doesn't match.");

        $user->setPassword($newPassword);
        $this->em->merge($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Delete an user and its links in the api
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user)
    {
        $userCountries = $this->em->getRepository(UserCountry::class)->findByUser($user);
        if (!empty($userCountries))
        {
            foreach ($userCountries as $userCountry)
            {
                $this->em->remove($userCountry);
            }
        }
        $userProjects = $this->em->getRepository(UserProject::class)->findByUser($user);
        if (!empty($userProjects))
        {
            foreach ($userProjects as $userProject)
            {
                $this->em->remove($userProject);
            }
        }

        try
        {
            $this->em->remove($user);
            $this->em->flush();
        }
        catch (\Exception $exception)
        {
            return false;
        }

        return true;
    }

    /**
     * Export all users in a CSV file
     * @param string $type
     * @return mixed
     */
    public function exportToCsv(string $type) {

        $exportableTable = $this->em->getRepository(User::class)->findAll();

        return $this->container->get('export_csv_service')->export($exportableTable,'users', $type);

    }
}
