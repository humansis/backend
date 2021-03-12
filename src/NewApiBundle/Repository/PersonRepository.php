<?php
declare(strict_types=1);

namespace NewApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\InputType\PersonFilterInputType;
use NewApiBundle\Request\Pagination;

class PersonRepository extends EntityRepository
{
    /**
     * @param Pagination|null            $pagination
     * @param PersonFilterInputType|null $filterInputType
     *
     * @return Paginator
     */
    public function findByParams(?Pagination $pagination = null, ?PersonFilterInputType $filterInputType = null): Paginator
    {
        $qb = $this->createQueryBuilder('p');

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        if ($filterInputType) {
            if ($filterInputType->hasIds()) {
                $qb->andWhere('p.id IN (:ids)')
                    ->setParameter('ids', $filterInputType->getIds());
            }
        }

        return new Paginator($qb);
    }
}
