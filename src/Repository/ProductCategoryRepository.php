<?php

declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Enum\ProductCategoryType;
use InputType\ProductCategoryFilterInputType;
use InputType\ProductCategoryOrderInputType;
use InvalidArgumentException;
use Request\Pagination;
use Entity\Vendor;

class ProductCategoryRepository extends EntityRepository
{
    public function findByFilter(
        ?ProductCategoryFilterInputType $filter = null,
        ?ProductCategoryOrderInputType $orderBy = null,
        ?Pagination $pagination = null
    ): Paginator {
        $qb = $this->createQueryBuilder('c')
            ->where('c.archived = 0');

        if ($filter) {
            if ($filter->hasIds()) {
                $qb->andWhere('c.id IN (:ids)')
                    ->setParameter('ids', $filter->getIds());
            }
            if ($filter->hasFulltext()) {
                $qb->andWhere('(c.id LIKE :fulltext OR c.name LIKE :fulltext)')
                    ->setParameter('fulltext', '%' . $filter->getFulltext() . '%');
            }
            if ($filter->hasVendors()) {
                $vendor = $this->getEntityManager()->getRepository(Vendor::class)->findOneBy(
                    ['id' => $filter->getVendors()]
                );
                $sellableCategoryTypes = [];
                if ($vendor->canSellFood()) {
                    $sellableCategoryTypes[] = ProductCategoryType::FOOD;
                }
                if ($vendor->canSellNonFood()) {
                    $sellableCategoryTypes[] = ProductCategoryType::NONFOOD;
                }
                if ($vendor->canSellCashback()) {
                    $sellableCategoryTypes[] = ProductCategoryType::CASHBACK;
                }

                $qb->andWhere('c.type in (:availableTypes)')
                    ->setParameter('availableTypes', $sellableCategoryTypes);
            }
        }

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                match ($name) {
                    ProductCategoryOrderInputType::SORT_BY_ID => $qb->orderBy('c.id', $direction),
                    ProductCategoryOrderInputType::SORT_BY_NAME => $qb->orderBy('c.name', $direction),
                    default => throw new InvalidArgumentException('Invalid order directive ' . $name),
                };
            }
        }

        return new Paginator($qb);
    }
}
