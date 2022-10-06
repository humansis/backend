<?php

namespace Repository;

use Entity\CountrySpecific;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use InputType\CountrySpecificFilterInputType;
use InputType\CountrySpecificOrderInputType;
use InvalidArgumentException;
use Request\Pagination;

/**
 * CountrySpecificRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @method CountrySpecific[] findByCountryIso3(string $iso)
 */
class CountrySpecificRepository extends EntityRepository
{
    public function findForCriteria(string $countryISO3)
    {
        return $this->findBy(['countryIso3' => $countryISO3]);
    }

    public function findByParams(
        string $countryIso3,
        ?CountrySpecificFilterInputType $filter,
        ?CountrySpecificOrderInputType $orderBy = null,
        ?Pagination $pagination = null
    ): Paginator {
        $qb = $this->createQueryBuilder('cs')
            ->andWhere('cs.countryIso3 = :iso3')
            ->setParameter('iso3', $countryIso3);

        if ($filter) {
            if ($filter->hasFulltext()) {
                $qb->andWhere('(cs.id LIKE :id OR cs.fieldString LIKE :fulltext)')
                    ->setParameter('id', $filter->getFulltext())
                    ->setParameter('fulltext', '%' . $filter->getFulltext() . '%');
            }
        }

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                switch ($name) {
                    case CountrySpecificOrderInputType::SORT_BY_ID:
                        $qb->orderBy('cs.id', $direction);
                        break;
                    case CountrySpecificOrderInputType::SORT_BY_FIELD:
                        $qb->orderBy('cs.fieldString', $direction);
                        break;
                    case CountrySpecificOrderInputType::SORT_BY_TYPE:
                        $qb->orderBy('cs.type', $direction);
                        break;
                    default:
                        throw new InvalidArgumentException('Invalid order by directive ' . $name);
                }
            }
        }

        return new Paginator($qb);
    }
}
