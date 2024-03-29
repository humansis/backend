<?php

declare(strict_types=1);

namespace Repository;

use InputType\Country;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Entity\SynchronizationBatch\Deposits;
use Entity\SynchronizationBatch\Purchases;
use InputType\SynchronizationBatch;
use InvalidArgumentException;
use Request\Pagination;

class SynchronizationBatchRepository extends EntityRepository
{
    public function findByParams(
        ?Country $country,
        ?Pagination $pagination = null,
        ?SynchronizationBatch\FilterInputType $filter = null,
        ?SynchronizationBatch\OrderInputType $orderBy = null
    ): Paginator {
        $qb = $this->createQueryBuilder('s');

        if ($filter) {
            if ($filter->hasFulltext()) {
                $qb->leftJoin('s.createdBy', 'u');
                $qb->leftJoin('u.vendor', 'v');
                $qb->andWhere(
                    '(
                    s.id LIKE :fulltextId OR
                    u.email LIKE :fulltext OR
                    u.username LIKE :fulltext OR
                    u.id LIKE :fulltextId OR
                    v.vendorNo LIKE :fulltextId OR
                    v.contractNo LIKE :fulltextId OR
                    v.name LIKE :fulltext
                )'
                );
                $qb->setParameter('fulltextId', $filter->getFulltext());
                $qb->setParameter('fulltext', '%' . $filter->getFulltext() . '%');
            }

            if ($filter->hasStates()) {
                $qb->andWhere('s.state IN (:states)')
                    ->setParameter('states', $filter->getStates());
            }

            if ($filter->hasType()) {
                $qb->andWhere('s INSTANCE OF :type');
                if ($filter->getType() == 'Deposit') {
                    $qb->setParameter('type', Deposits::class);
                }
                if ($filter->getType() == 'Purchase') {
                    $qb->setParameter('type', Purchases::class);
                }
            }
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                match ($name) {
                    SynchronizationBatch\OrderInputType::SORT_BY_ID => $qb->orderBy('s.id', $direction),
                    SynchronizationBatch\OrderInputType::SORT_BY_SOURCE => $qb->orderBy('s.source', $direction),
                    SynchronizationBatch\OrderInputType::SORT_BY_DATE => $qb->orderBy('s.createdAt', $direction),
                    default => throw new InvalidArgumentException('Invalid order by directive ' . $name),
                };
            }
        }

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        return new Paginator($qb);
    }
}
