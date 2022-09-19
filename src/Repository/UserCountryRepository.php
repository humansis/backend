<?php

namespace Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Entity\UserCountry;

/**
 * UserCountryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserCountryRepository extends EntityRepository
{
    /**
     * @param UserCountry $userCountry
     *
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(UserCountry $userCountry): void
    {
        $this->_em->persist($userCountry);
        $this->_em->flush();
    }
}