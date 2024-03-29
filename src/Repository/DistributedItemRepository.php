<?php

declare(strict_types=1);

namespace Repository;

use DateTime;
use DateTimeInterface;
use Entity\Beneficiary;
use Entity\Household;
use Entity\Location;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Entity\DistributedItem;
use Enum\NationalIdType;
use InputType\DistributedItemFilterInputType;
use InputType\DistributedItemOrderInputType;
use InvalidArgumentException;
use Request\Pagination;
use Utils\DateTime\DateTimeFilter;
use Utils\DateTime\Iso8601Converter;

class DistributedItemRepository extends EntityRepository
{
    /**
     *
     * @return Paginator|DistributedItem[]
     */
    public function findByParams(
        string $countryIso3,
        ?DistributedItemFilterInputType $filter = null,
        ?DistributedItemOrderInputType $orderBy = null,
        ?Pagination $pagination = null
    ): Paginator {
        $qbr = $this->createQueryBuilder('di')
            ->join('di.project', 'pr')
            ->andWhere('pr.countryIso3 = :iso3')
            ->setParameter('iso3', $countryIso3);

        if ($filter) {
            if ($filter->hasBeneficiaryTypes()) {
                $qbr->andWhere('di.beneficiaryType IN (:bnfType)')
                    ->setParameter('bnfType', $filter->getBeneficiaryTypes());
            }
            if ($filter->hasFulltext()) {
                $qbr->join('di.beneficiary', 'b')
                    ->join('b.person', 'p')
                    ->leftJoin('p.nationalIds', 'ni')
                    ->andWhere(
                        '(b.id = :fulltext OR
                                p.localGivenName LIKE :fulltextLike OR
                                p.localFamilyName LIKE :fulltextLike OR
                                p.localParentsName LIKE :fulltextLike OR
                                p.enParentsName LIKE :fulltextLike OR
                                (ni.idNumber LIKE :fulltextLike AND ni.idType = :niType))'
                    )
                    ->setParameter('niType', NationalIdType::NATIONAL_ID)
                    ->setParameter('fulltext', $filter->getFulltext())
                    ->setParameter('fulltextLike', '%' . $filter->getFulltext() . '%');
            }
            if ($filter->hasProjects()) {
                $qbr->andWhere('pr.id IN (:projects)')
                    ->setParameter('projects', $filter->getProjects());
            }
            if ($filter->hasAssistances() && count($filter->getAssistances()) === 1) {
                $qbr->andWhere('IDENTITY(di.assistance) = :assistance')
                    ->setParameter('assistance', $filter->getAssistances()[0]);
            }
            if ($filter->hasAssistances() && count($filter->getAssistances()) > 1) {
                $qbr->andWhere('IDENTITY(di.assistance) IN (:assistances)')
                    ->setParameter('assistances', $filter->getAssistances());
            }
            if ($filter->hasLocations()) {
                /** @var LocationRepository $locationRepository */
                $locationRepository = $this->_em->getRepository(Location::class);
                $location = $locationRepository->find($filter->getLocations()[0]);

                if ($location === null || $location->getCountryIso3() !== $countryIso3) {
                    throw new InvalidArgumentException("Location not found or in different country");
                }

                $qbr = $locationRepository->joinChildrenLocationsQueryBuilder($qbr, $location, 'di', 'l', true);
            }
            if ($filter->hasModalityTypes()) {
                $qbr->andWhere('di.modalityType IN (:modalityTypes)')
                    ->setParameter('modalityTypes', $filter->getModalityTypes());
            }
            if ($filter->hasDateFrom()) {
                $qbr->andWhere('di.dateDistribution >= :dateFrom')
                    ->setParameter('dateFrom', DateTimeFilter::getDateTimeFromFilterDate($filter->getDateFrom()));
            }
            if ($filter->hasDateTo()) {
                $qbr->andWhere('di.dateDistribution < :dateTo')
                    ->setParameter('dateTo', DateTimeFilter::getDateTimeFromFilterDate($filter->getDateTo(), true));
            }
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                switch ($name) {
                    case DistributedItemOrderInputType::SORT_BY_BENEFICIARY_ID:
                        if (!in_array('b', $qbr->getAllAliases())) {
                            $qbr->leftJoin('di.beneficiary', 'b');
                        }
                        $qbr->addOrderBy('b.id', $direction);
                        break;
                    case DistributedItemOrderInputType::SORT_BY_DISTRIBUTION_DATE:
                        $qbr->addOrderBy('di.dateDistribution', $direction);
                        break;
                    case DistributedItemOrderInputType::SORT_BY_AMOUNT:
                        $qbr->addOrderBy('di.amount', $direction);
                        break;
                    default:
                        throw new InvalidArgumentException('Invalid order by directive ' . $name);
                }
            }
        }

        if ($pagination) {
            $qbr->setMaxResults($pagination->getLimit())
                ->setFirstResult($pagination->getOffset());
        }

        $qbr->addOrderBy('di.dateDistribution', 'DESC');

        return new Paginator($qbr);
    }

    /**
     * @return Paginator|DistributedItem[]
     */
    public function findByBeneficiary(Beneficiary $beneficiary): Paginator
    {
        $qbr = $this->createQueryBuilder('di')
            ->andWhere('di.beneficiaryType = :type')
            ->andWhere('di.beneficiary = :beneficiary')
            ->setParameter('type', 'Beneficiary')
            ->setParameter('beneficiary', $beneficiary);

        return new Paginator($qbr);
    }

    /**
     * @return Paginator|DistributedItem[]
     */
    public function findByHousehold(Household $household): Paginator
    {
        $qbr = $this->createQueryBuilder('di')
            ->andWhere('di.beneficiaryType = :type')
            ->andWhere('di.beneficiary = :household')
            ->setParameter('type', 'Household')
            ->setParameter('household', $household);

        return new Paginator($qbr);
    }
}
