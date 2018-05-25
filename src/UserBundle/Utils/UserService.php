<?php

namespace UserBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use UserBundle\Entity\User;

class UserService
{
    protected $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
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
        return $this->em->getRepository(User::class)->edit($user, $userData);
    }

    public function getSalt(string $username)
    {
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

        return $user->getSalt();
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

        $this->em->merge($user);
        $this->em->flush();

        return $user;
    }
}
