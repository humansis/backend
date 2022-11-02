<?php

namespace Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use InputType\TransactionFilterInputType;

/**
 * TransactionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TransactionRepository extends EntityRepository
{
    public function findByParams(?TransactionFilterInputType $filter = null): Paginator
    {
        $qb = $this->createQueryBuilder('t');

        if ($filter) {
            if ($filter->hasIds()) {
                $qb->andWhere('t.id IN (:ids)');
                $qb->setParameter('ids', $filter->getIds());
            }
        }

        return new Paginator($qb);
    }
}
