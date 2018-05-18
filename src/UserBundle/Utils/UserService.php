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
     * @return \Doctrine\ORM\EntityRepository|\UserBundle\Repository\UserRepository
     */
    private function getRepository()
    {
        return $this->em->getRepository('UserBundle:User');
    }
}
