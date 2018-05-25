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

    }
}
