<?php
declare(strict_types=1);

namespace NewApiBundle\Repository;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\NationalId;
use CommonBundle\Entity\Location;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\Entity\SmartcardPurchasedItem;
use NewApiBundle\InputType\PurchasedItemOrderInputType;
use NewApiBundle\InputType\SmartcardPurchasedItemFilterInputType;
use NewApiBundle\Request\Pagination;

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
                    ->andWhere("hh.id = IDENTITY(pi.beneficiary)")
                    ->andWhere("(p2.localGivenName LIKE :fulltextLike OR 
                                p2.localFamilyName LIKE :fulltextLike OR
                                p2.localParentsName LIKE :fulltextLike OR
                                p2.enParentsName LIKE :fulltextLike OR
                                (ni2.idNumber LIKE :fulltextLike AND ni2.idType = :niType))")
                    ->getDQL()
                ;

                $qbr->join('pi.vendor', 'v');
                $qbr->andWhere("IDENTITY(pi.beneficiary) = :fulltext 
                        OR (pi.beneficiaryType = 'Beneficiary' AND EXISTS($subQueryForBNFFulltext))
                        OR (pi.beneficiaryType = 'Household' AND EXISTS($subQueryForHHFulltext))
                        OR pi.carrierNumber LIKE :fulltextLike
                        OR v.vendorNo LIKE :fulltextLike
                        OR pi.invoiceNumber LIKE :fulltext
                        ")
                    ->setParameter('fulltext', $filter->getFulltext())
                    ->setParameter('fulltextLike', '%'.$filter->getFulltext().'%')
                    ->setParameter('niType', NationalId::TYPE_NATIONAL_ID)
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
                $locationIds = [];
                foreach ($filter->getLocations() as $location) {
                    $locationIds = array_merge($locationIds, $this->_em->getRepository(Location::class)->findDescendantLocations($location));
                }

                $qbr
                    ->andWhere('IDENTITY(pi.location) IN (:locations)')
                    ->setParameter('locations', $locationIds);
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

        $qbr->addOrderBy('pi.datePurchase', 'ASC');

        return new Paginator($qbr);
    }
}
