<?php

namespace UserBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UserBundle\Entity\User;

class UserService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ValidatorInterface $validator */
    private $validator;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->em = $entityManager;
        $this->validator = $validator;
    }

    /**
     * @return array
     */
    public function findAll()
    {
        return $this->em->getRepository(User::class)->findAll();
    }

    /**
     * @param  string $username
     * @return User
     */
    public function getUserByUsername(string $username)
    {
        return $this->em->getRepository(User::class)->findOneByUsername($username);
    }

    public function update(User $user, array $userData)
    {
        $this->em->getRepository(User::class)->edit($user, $userData);

        return $this->em->getRepository(User::class)->find($user->getId());
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
                ->setEmail($salt)
                ->setEmailCanonical($salt)
                ->setSalt($salt)
                ->setPassword("");


            $this->em->persist($user);

            $this->em->flush();
        }

        return ["user_id" => $user->getId(), "salt" => $user->getSalt()];
    }

    public function login(string $username, string $saltedPassword)
    {
        $repository = $this->em->getRepository('UserBundle:User');

        $user = $repository->findOneBy([
            'username' => $username,
            'password' => $saltedPassword,
        ]);

        if ($user instanceOf User)
        {
            $data = [
                'at' => time(),
                'connected' => true,
                'user' => $user->getUsername()
            ];

        }
        else
        {
            $user = $repository->findOneBy([
                'username' => $username
            ]);
            if ($user instanceOf User)
            {
                $user->setPassword($saltedPassword);
                $this->em->persist($user);
                $this->em->flush();

                $data = [
                    'at' => time(),
                    'registered' => true,
                    'user' => $user->getUsername()
                ];
            }
            else
            {
                throw new \Exception('Bad credentials (username: ' . $username . ', password: ' . $saltedPassword . ')');
            }

        }

        return $data;

    }

    /**
     * @param User $user
     * @return mixed
     * @throws \Exception
     */
    public function create(User $user)
    {
        $userSaved = $this->em->getRepository(User::class)->findOneByUsername($user->getUsername());
        if (!$userSaved instanceof User)
            throw new \Exception("The user with username {$user->getUsername()} has been not preconfigured. You need to ask 
            the salt for this username before.");
        elseif ($userSaved->isEnabled())
            throw new \Exception("The user with username {$user->getUsername()} has been already add");

        $user->setId($userSaved->getId())
            ->setSalt($userSaved->getSalt())
            ->setEmailCanonical($user->getEmail())
            ->setEnabled(1);

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

        $this->em->merge($user);
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
        if ($user->getPassword() !== $oldPassword)
            throw new \Exception("The old password doesn't match.");

        $user->setPassword($newPassword);
        $this->em->merge($user);
        $this->em->flush();

        return $user;
    }
}
