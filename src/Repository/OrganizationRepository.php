<?php

namespace Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Entity\Organization;
use Repository\Helper\TRepositoryHelper;
use Request\Pagination;

/**
 * OrganizationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class OrganizationRepository extends EntityRepository
{
    use TRepositoryHelper;

    public function findByParams(?Pagination $pagination = null): Paginator
    {
        $qb = $this->createQueryBuilder('o');

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        return new Paginator($qb);
    }
}
