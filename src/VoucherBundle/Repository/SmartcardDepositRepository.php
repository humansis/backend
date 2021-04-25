<?php

declare(strict_types=1);

namespace VoucherBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\InputType\SmartcardDepositFilterInputType;

class SmartcardDepositRepository extends EntityRepository
{
    /**
     * @param SmartcardDepositFilterInputType|null $filter
     *
     * @return Paginator
     */
    public function findByParams(?SmartcardDepositFilterInputType $filter = null): Paginator
    {
        $qb = $this->createQueryBuilder('sd');

        if ($filter) {
            if ($filter->hasIds()) {
                $qb->andWhere('sd.id IN (:ids)');
                $qb->setParameter('ids', $filter->getIds());
            }
        }

        return new Paginator($qb);
    }
}
