<?php

namespace VoucherBundle\Repository;

use CommonBundle\Entity\Location;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\InputType\VendorFilterInputType;
use NewApiBundle\InputType\VendorOrderInputType;
use NewApiBundle\Request\Pagination;
use UserBundle\Entity\User;

/**
 * VendorRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class VendorRepository extends \Doctrine\ORM\EntityRepository
{
    public function getVendorByUser(User $user)
    {
        $qb = $this->createQueryBuilder('v');
        $q = $qb->where('v.user = :user')
            ->setParameter('user', $user);

        return $q->getQuery()->getResult();
    }

    public function getVendorCountry(User $user)
    {
        $qb = $this->createQueryBuilder('v');
        $q = $qb->where('v.user = :user')
                ->setParameter('user', $user)
                ->leftJoin('v.location', 'l');

        $locationRepository = $this->getEntityManager()->getRepository(Location::class);
        $locationRepository->getCountry($q);

        return $q->getQuery()->getSingleResult()['country'];
    }

    public function findByCountry($countryISO3)
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.archived = false')
            ->leftJoin('v.location', 'l');
        $locationRepository = $this->getEntityManager()->getRepository(Location::class);
        $locationRepository->whereCountry($qb, $countryISO3);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string|null                $iso3
     * @param VendorFilterInputType|null $filter
     * @param VendorOrderInputType|null  $orderBy
     * @param Pagination|null            $pagination
     *
     * @return Paginator
     */
    public function findByParams(
        ?string $iso3,
        ?VendorFilterInputType $filter = null,
        ?VendorOrderInputType $orderBy = null,
        ?Pagination $pagination = null
    ): Paginator
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.location', 'l')
            ->andWhere('v.archived = 0');

        $locationRepository = $this->getEntityManager()->getRepository(Location::class);
        $locationRepository->whereCountry($qb, $iso3);

        if ($filter) {
            if ($filter->hasFulltext()) {
                $qb->andWhere('(v.id = :fulltextId OR
                                v.shop LIKE :fulltext OR
                                v.name LIKE :fulltext OR
                                v.addressNumber LIKE :fulltext OR
                                v.addressPostcode LIKE :fulltext OR
                                v.addressStreet LIKE :fulltext)')
                    ->setParameter('fulltextId', $filter->getFulltext())
                    ->setParameter('fulltext', '%'.$filter->getFulltext().'%');
            }
        }

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                switch ($name) {
                    case VendorOrderInputType::SORT_BY_ID:
                        $qb->orderBy('v.id', $direction);
                        break;
                    case VendorOrderInputType::SORT_BY_NAME:
                        $qb->orderBy('v.name', $direction);
                        break;
                    case VendorOrderInputType::SORT_BY_SHOP:
                        $qb->orderBy('v.shop', $direction);
                        break;
                    case VendorOrderInputType::SORT_BY_USERNAME:
                        $qb->leftJoin('v.user', 'u')
                            ->orderBy('u.username', $direction);
                        break;
                    case VendorOrderInputType::SORT_BY_ADDRESS_STREET:
                        $qb->orderBy('v.addressStreet', $direction);
                        break;
                    case VendorOrderInputType::SORT_BY_ADDRESS_NUMBER:
                        $qb->orderBy('v.addressNumber', $direction);
                        break;
                    case VendorOrderInputType::SORT_BY_ADDRESS_POSTCODE:
                        $qb->orderBy('v.addressPostcode', $direction);
                        break;
                    case VendorOrderInputType::SORT_BY_LOCATION:
                        $qb->addSelect('
                            CASE WHEN adm4.id IS NOT NULL THEN adm4.name ELSE 
                                CASE WHEN adm3.id IS NOT NULL THEN adm3.name ELSE
                                    CASE WHEN adm2.id IS NOT NULL THEN adm2.name ELSE
                                        CASE WHEN adm1.id IS NOT NULL THEN adm1.name ELSE 0 END
                                    END
                                END   
                            END
                         as HIDDEN admName');

                        $qb->addOrderBy('admName', $direction);

                        break;
                    default:
                        throw new \InvalidArgumentException('Invalid order by directive '.$name);
                }
            }
        }

        return new Paginator($qb);
    }
}
