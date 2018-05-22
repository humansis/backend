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
        return $this->getRepository()->findAll();
    }

	/**
	 * @param  string $username
	 * @return User
	 */
	public function getUserByUsername(string $username)
	{
		return $this->getRepository()->findOneBy(array('username' => $username));
	}

	/**
     * @return \Doctrine\ORM\EntityRepository|\UserBundle\Repository\UserRepository
     */
    private function getRepository()
    {
        return $this->em->getRepository('UserBundle:User');
    }
}
