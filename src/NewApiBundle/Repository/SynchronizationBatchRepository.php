<?php
declare(strict_types=1);

namespace NewApiBundle\Repository;

use CommonBundle\InputType\Country;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\InputType\SynchronizationBatchFilterInputType;
use NewApiBundle\InputType\SynchronizationBatchOrderInputType;
use NewApiBundle\Request\Pagination;

class SynchronizationBatchRepository extends EntityRepository
{
    public function findByParams(?Country $country, ?Pagination $pagination = null, ?SynchronizationBatchFilterInputType $filter = null, ?SynchronizationBatchOrderInputType $orderBy = null): Paginator
    {
        $qb = $this->createQueryBuilder('s');

        if ($filter) {
            if ($filter->hasFulltext()) {
                $qb->leftJoin('s.createdBy', 'u');
                $qb->andWhere('(
                    s.id LIKE :fulltextId OR
                    u.email LIKE :fulltext OR
                    u.username LIKE :fulltext OR
                    u.id LIKE :fulltextId
                )');
                $qb->setParameter('fulltextId', $filter->getFulltext());
                $qb->setParameter('fulltext', '%'.$filter->getFulltext().'%');
            }

            if ($filter->hasStates()) {
                $qb->andWhere('s.state IN (:states)')
                    ->setParameter('states', $filter->getStates());
            }

            if ($filter->hasType()) {
                $qb->andWhere('s.validationType = :type')
                    ->setParameter('type', $filter->getType());
            }
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                switch ($name) {
                    case SynchronizationBatchOrderInputType::SORT_BY_ID:
                        $qb->orderBy('s.id', $direction);
                        break;
                    case SynchronizationBatchOrderInputType::SORT_BY_SOURCE:
                        $qb->orderBy('s.source', $direction);
                        break;
                    case SynchronizationBatchOrderInputType::SORT_BY_TYPE:
                        $qb->orderBy('s.validationType', $direction);
                        break;
                    case SynchronizationBatchOrderInputType::SORT_BY_DATE:
                        $qb->orderBy('s.createdAt', $direction);
                        break;
                    default:
                        throw new \InvalidArgumentException('Invalid order by directive '.$name);
                }
            }
        }

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        return new Paginator($qb);
    }
}
