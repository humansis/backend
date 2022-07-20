<?php

namespace DistributionBundle\Repository;

use NewApiBundle\Entity\Beneficiary;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\GeneralReliefItem;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\InputType\GeneralReliefFilterInputType;
use NewApiBundle\Request\Pagination;

/**
 * GeneralReliefItemRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class GeneralReliefItemRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param GeneralReliefFilterInputType $filter
     * @param Pagination|null              $pagination
     *
     * @return Paginator|GeneralReliefItem[]
     */
    public function findByParams(GeneralReliefFilterInputType $filter, ?Pagination $pagination = null): Paginator
    {
        $qbr = $this->createQueryBuilder('gri');

        if ($pagination) {
            $qbr->setMaxResults($pagination->getLimit())
                ->setFirstResult($pagination->getOffset());
        }

        if ($filter->hasIds()) {
            $qbr->andWhere('gri.id IN (:ids)')
                ->setParameter('ids', $filter->getIds());
        }

        return new Paginator($qbr);
    }
}
