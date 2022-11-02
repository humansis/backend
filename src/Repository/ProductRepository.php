<?php

namespace Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Enum\ProductCategoryType;
use InputType\ProductFilterInputType;
use InputType\ProductOrderInputType;
use InvalidArgumentException;
use Request\Pagination;
use Entity\Product;
use Entity\Vendor;

/**
 * ProductRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ProductRepository extends EntityRepository
{
    public function getByCategoryType(?string $country, string $categoryType)
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.productCategory', 'c')
            ->where('c.type = :type')
            ->andWhere('p.archived = 0')
            ->setParameter('type', $categoryType);

        if ($country) {
            $qb->andWhere('p.countryIso3 = :country')
                ->setParameter('country', $country);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     *
     * @return Paginator|Product[]
     */
    public function findByCountry(
        string $countryIso3,
        ?ProductFilterInputType $filter = null,
        ?ProductOrderInputType $orderBy = null,
        ?Pagination $pagination = null
    ): Paginator {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.productCategory', 'c')
            ->andWhere('p.archived = 0')
            ->andWhere('p.countryIso3 = :countryIso3')
            ->setParameter('countryIso3', $countryIso3);

        if ($filter) {
            if ($filter->hasIds()) {
                $qb->andWhere('p.id IN (:ids)')
                    ->setParameter('ids', $filter->getIds());
            }
            if ($filter->hasFulltext()) {
                $qb->andWhere('(p.id LIKE :fulltext OR p.name LIKE :fulltext OR p.unit LIKE :fulltext)')
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

                $qb->andWhere('p.productCategory IS NOT NULL');
                $qb->andWhere('c.type in (:availableTypes)')
                    ->setParameter('availableTypes', $sellableCategoryTypes);
                if ($vendor->getLocation()) {
                    $qb->andWhere('p.countryIso3 = :vendorCountry')
                        ->setParameter('vendorCountry', $vendor->getLocation()->getCountryIso3());
                }
            }
        }

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                match ($name) {
                    ProductOrderInputType::SORT_BY_ID => $qb->orderBy('p.id', $direction),
                    ProductOrderInputType::SORT_BY_NAME => $qb->orderBy('p.name', $direction),
                    ProductOrderInputType::SORT_BY_UNIT => $qb->orderBy('p.unit', $direction),
                    ProductOrderInputType::SORT_BY_CATEGORY => $qb->orderBy('c.name', $direction),
                    default => throw new InvalidArgumentException('Invalid order directive ' . $name),
                };
            }
        }

        return new Paginator($qb);
    }
}
