<?php
declare(strict_types=1);

namespace Repository;

use Entity\Beneficiary;
use Entity\Location;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Entity\SmartcardPurchasedItem;
use Enum\NationalIdType;
use InputType\PurchasedItemOrderInputType;
use InputType\SmartcardPurchasedItemFilterInputType;
use Request\Pagination;

class SmartcardPurchasedItemRepository  extends EntityRepository
{
    /**
     * @param string                            $countryIso3
     * @param SmartcardPurchasedItemFilterInputType|null $filter
     * @param PurchasedItemOrderInputType|null  $orderBy
     * @param Pagination|null                   $pagination
     *
     * @return Paginator|SmartcardPurchasedItem[]
     */
    public function findByParams(
        string $countryIso3,
        ?SmartcardPurchasedItemFilterInputType $filter = null,
        ?PurchasedItemOrderInputType $orderBy = null,
        ?Pagination $pagination = null
    ): Paginator
    {
        $qbr = $this->createQueryBuilder('pi')
            ->join('pi.project', 'pr')
            ->andWhere('pr.iso3 = :iso3')
            ->setParameter('iso3', $countryIso3);

        if ($filter) {
            if ($filter->hasFulltext()) {
                $subQueryForBNFFulltext = $this->_em->createQueryBuilder()
                    ->select("beneficiary.id")
                    ->from(Beneficiary::class, 'beneficiary')
                    ->leftJoin("beneficiary.person", 'p1')
                    ->leftJoin('p1.nationalIds', 'ni1')
                    ->andWhere("beneficiary.id = IDENTITY(pi.beneficiary)")
                    ->andWhere("(p1.localGivenName LIKE :fulltextLike OR 
                                p1.localFamilyName LIKE :fulltextLike OR
                                p1.localParentsName LIKE :fulltextLike OR
                                p1.enParentsName LIKE :fulltextLike OR
                                (ni1.idNumber LIKE :fulltextLike AND ni1.idType = :niType))")
                    ->getDQL()
                ;

                $subQueryForHHFulltext = $this->_em->createQueryBuilder()
                    ->select("hhm.id")
                    ->from(Beneficiary::class, 'hhm')
                    ->leftJoin("hhm.person", 'p2')
                    ->leftJoin("hhm.household", 'hh')
                    ->leftJoin('p2.nationalIds', 'ni2')
                    ->andWhere("hh.id = IDENTITY(pi.household)")
                    ->andWhere("(p2.localGivenName LIKE :fulltextLike OR 
                                p2.localFamilyName LIKE :fulltextLike OR
                                p2.localParentsName LIKE :fulltextLike OR
                                p2.enParentsName LIKE :fulltextLike OR
                                (ni2.idNumber LIKE :fulltextLike AND ni2.idType = :niType))")
                    ->getDQL()
                ;

                $qbr->join('pi.vendor', 'v');
                $qbr->andWhere("IDENTITY(pi.beneficiary) = :fulltext 
                        OR EXISTS($subQueryForBNFFulltext)
                        OR EXISTS($subQueryForHHFulltext)
                        OR pi.smartcardCode LIKE :fulltextLike
                        OR v.vendorNo LIKE :fulltextLike
                        OR pi.invoiceNumber LIKE :fulltext
                        ")
                    ->setParameter('fulltext', $filter->getFulltext())
                    ->setParameter('fulltextLike', '%'.$filter->getFulltext().'%')
                    ->setParameter('niType', NationalIdType::NATIONAL_ID)
                ;
            }
            if ($filter->hasProjects()) {
                $qbr->andWhere('pr.id IN (:projects)')
                    ->setParameter('projects', $filter->getProjects());
            }
            if ($filter->hasAssistances()) {
                $qbr->join('pi.assistance', 'ass')
                    ->andWhere('ass.id IN (:assistances)')
                    ->setParameter('assistances', $filter->getAssistances());
            }
            if ($filter->hasLocations()) {

                /** @var LocationRepository $locationRepository */
                $locationRepository = $this->_em->getRepository(Location::class);
                $location = $locationRepository->find($filter->getLocations()[0]);

                if ($location === null || $location->getCountryISO3() !== $countryIso3) {
                    throw new \InvalidArgumentException("Location not found or in different country");
                }

                $qbr = $locationRepository->joinChildrenLocationsQueryBuilder($qbr, $location, 'pi', 'l', true);

            }
            if ($filter->hasVendors()) {
                $qbr->andWhere('pi.vendor IN (:vendors)')
                    ->setParameter('vendors', $filter->getVendors());
            }
            if ($filter->hasDateFrom()) {
                $qbr->andWhere('pi.datePurchase >= :dateFrom')
                    ->setParameter('dateFrom', $filter->getDateFrom());
            }
            if ($filter->hasDateTo()) {
                $qbr->andWhere('pi.datePurchase <= :dateTo')
                    ->setParameter('dateTo', $filter->getDateTo());
            }
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                switch ($name) {
                    case PurchasedItemOrderInputType::SORT_BY_DATE_PURCHASE:
                        $qbr->addOrderBy('pi.datePurchase', $direction);
                        break;
                    case PurchasedItemOrderInputType::SORT_BY_VALUE:
                        $qbr->addOrderBy('pi.value', $direction);
                        break;
                    default:
                        throw new \InvalidArgumentException('Invalid order by directive '.$name);
                }
            }
        }

        if ($pagination) {
            $qbr->setMaxResults($pagination->getLimit())
                ->setFirstResult($pagination->getOffset());
        }

        $qbr->addOrderBy('pi.datePurchase', 'DESC');

        return new Paginator($qbr);
    }
}
