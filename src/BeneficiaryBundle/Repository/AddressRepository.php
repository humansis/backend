<?php

namespace BeneficiaryBundle\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\InputType\AddressFilterInputType;

/**
 * AddressRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AddressRepository extends \Doctrine\ORM\EntityRepository
{
    public function findByParams(AddressFilterInputType $filter): Paginator
    {
        $qbr = $this->createQueryBuilder('a');

        if ($filter) {
            if ($filter->hasIds()) {
                $qbr->andWhere('a.id IN (:ids)')
                    ->setParameter('ids', $filter->getIds());
            }
        }

        return new Paginator($qbr);
    }
}