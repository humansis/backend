<?php

namespace Repository;

use Doctrine\ORM\EntityRepository;
use Entity\Phone;
use Doctrine\ORM\Tools\Pagination\Paginator;
use InputType\PhoneFilterInputType;

/**
 * PhoneRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PhoneRepository extends EntityRepository
{
    /**
     * @return Paginator|Phone[]
     */
    public function findByParams(PhoneFilterInputType $filter): Paginator
    {
        $qbr = $this->createQueryBuilder('p')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $filter->getIds());

        return new Paginator($qbr);
    }
}
