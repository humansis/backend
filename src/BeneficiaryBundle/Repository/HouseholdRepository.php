<?php

namespace BeneficiaryBundle\Repository;

use NewApiBundle\Entity\Household;
use NewApiBundle\Entity\HouseholdLocation;
use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\HouseholdLocation;
use CommonBundle\Repository\LocationRepository;
use DistributionBundle\Repository\AbstractCriteriaRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\InputType\HouseholdFilterInputType;
use NewApiBundle\InputType\HouseholdOrderInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\DBAL\LivelihoodEnum;
use ProjectBundle\Entity\Project;
use CommonBundle\Entity\Location;
use Doctrine\ORM\Query\Expr\Join;

/**
 * HouseholdRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class HouseholdRepository extends AbstractCriteriaRepository
{
    /**
     * @var LocationRepository
     */
    private $locationRepository;

    public function injectLocationRepository(LocationRepository $locationRepository)
    {
        $this->locationRepository = $locationRepository;
    }

    /**
     * Find all households in country
     * @param  string $iso3
     * @return QueryBuilder
     */
    public function findAllByCountry(string $iso3)
    {
        $qb = $this->createQueryBuilder("hh");
        $this->whereHouseholdInCountry($qb, $iso3);
        $qb->andWhere('hh.archived = 0');

        return $qb;
    }

    public function getUnarchivedByProject(Project $project)
    {
        $qb = $this->createQueryBuilder("hh");
        $q = $qb->leftJoin("hh.projects", "p")
                ->where("p = :project")
                ->setParameter("project", $project)
                ->andWhere("hh.archived = 0");

        return $q;
    }

    public function countUnarchivedByCountryProjects(string $iso3): int
    {
        $qb = $this->createQueryBuilder("hh");
        $qb
            ->select("COUNT(DISTINCT hh)")
            ->leftJoin("hh.projects", "p")
            ->where("p.iso3 = :country")
            ->setParameter("country", $iso3)
            ->andWhere("hh.archived = 0")
        ;
        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        }
    }

    public function countUnarchivedByProject(Project $project)
    {
        $qb = $this
            ->createQueryBuilder("hh")
            ->select("COUNT(hh)")
            ->leftJoin("hh.projects", "p")
            ->where("p = :project")
            ->setParameter("project", $project)
            ->andWhere("hh.archived = 0");

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Return households which a Levenshtein distance with the stringToSearch under minimumTolerance
     * @param string $iso3
     * @param string $stringToSearch
     * @param int $minimumTolerance
     * @return mixed
     */
    public function foundSimilarAddressLevenshtein(string $iso3, string $stringToSearch, int $minimumTolerance)
    {
        $qb = $this->findAllByCountry($iso3);
        $q = $qb->leftJoin("hh.beneficiaries", "b")
            ->leftJoin('b.person', 'p')
            // Those leftJoins are already done in findAllByCountry
            // ->leftJoin("hh.householdLocations", "hl")
            // ->leftJoin("hl.address", "ad")
            ->select("hh as household")
            ->andWhere("hh.archived = 0")
            ->addSelect(
                "LEVENSHTEIN(
                    CONCAT(
                        COALESCE(ad.street, ''),
                        COALESCE(ad.number, ''),
                        COALESCE(ad.postcode, ''),
                        COALESCE(p.localGivenName, ''),
                        COALESCE(p.localFamilyName, '')
                    ),
                    :stringToSearch
                ) as levenshtein")
            ->andWhere("b.status = 1")
            ->groupBy("b, ad")
            ->having("levenshtein <= :minimumTolerance")
            ->setParameter("stringToSearch", $stringToSearch)
            ->setParameter("minimumTolerance", $minimumTolerance)
            ->orderBy("levenshtein", "ASC");

        $query = $q->getQuery();
        $query->useResultCache(true,600);

        return $query->getResult();
    }

    /**
     * Return households which a Levenshtein distance with the stringToSearch under minimumTolerance
     * @param string $iso3
     * @param string $stringToSearch
     * @param int $minimumTolerance
     * @return mixed
     */
    public function foundSimilarCampLevenshtein(string $iso3, string $stringToSearch, int $minimumTolerance)
    {
        $qb = $this->findAllByCountry($iso3);
        $q = $qb->leftJoin("hh.beneficiaries", "b")
            ->leftJoin('b.person', 'p')
            // Those leftJoins are already done in findAllByCountry
            // ->leftJoin("hh.householdLocations", "hl")
            // ->leftJoin("hl.campAddress", "ca")
            // ->leftJoin("ca.camp", "c")
            ->select("hh as household")
            ->andWhere("hh.archived = 0")
            ->addSelect(
                "LEVENSHTEIN(
                    CONCAT(
                        COALESCE(c.name, ''),
                        COALESCE(ca.tentNumber, ''),
                        COALESCE(p.localGivenName, ''),
                        COALESCE(p.localFamilyName, '')
                    ),
                    :stringToSearch
                ) as levenshtein"
            )
            ->andWhere("b.status = 1")
            ->groupBy("b, c, ca")
            ->having("levenshtein <= :minimumTolerance")
            ->setParameter("stringToSearch", $stringToSearch)
            ->setParameter("minimumTolerance", $minimumTolerance)
            ->orderBy("levenshtein", "ASC");

        $query = $q->getQuery();
        $query->useResultCache(true,600);

        return $query->getResult();
    }

    /**
     * @param string                       $iso3
     * @param HouseholdFilterInputType     $filter
     * @param HouseholdOrderInputType|null $orderBy
     * @param Pagination|null              $pagination
     *
     * @return Paginator|Household[]
     */
    public function findByParams(
        string $iso3,
        HouseholdFilterInputType $filter,
        HouseholdOrderInputType $orderBy = null,
        ?Pagination $pagination = null
    ): Paginator {
        $qb = $this->createQueryBuilder('hh');
        $this->getHouseholdLocation($qb);
        
        $qb->leftJoin('hh.beneficiaries', 'b')
            ->leftJoin('hh.projects', 'p')
            ->leftJoin('b.vulnerabilityCriteria', 'vb')
            ->leftJoin('b.person', 'per')
            ->leftJoin('per.nationalIds', 'ni')
            ->leftJoin('per.referral', 'r')
            ->leftJoin('hh.beneficiaries', 'head', Join::WITH, 'head.status = 1')
            ->leftJoin('head.person', 'headper')
            ->andWhere('hh.archived = 0')
            ->andWhere('l.countryISO3 = :iso3')
            ->setParameter('iso3', $iso3);

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        if ($filter->hasIds()) {
            $qb->andWhere("hh.id IN (:ids)")
                ->setParameter('ids', $filter->getIds());
        }

        if ($filter->hasFulltext()) {

            $qbl1 = $this->locationRepository->addParentLocationFulltextSubQueryBuilder(1, 'l', 'l1');
            $qbl2 = $this->locationRepository->addParentLocationFulltextSubQueryBuilder(2, 'l', 'l2');
            $qbl3 = $this->locationRepository->addParentLocationFulltextSubQueryBuilder(3, 'l', 'l3');

            $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->like("CONCAT(
                            COALESCE(hh.id, ''),
                            COALESCE(per.enFamilyName, ''),
                            COALESCE(per.enGivenName, ''),
                            COALESCE(per.localFamilyName, ''),
                            COALESCE(per.localGivenName, ''),
                            COALESCE(p.name, ''),
                            COALESCE(l.name, ''),
                            COALESCE(vb.fieldString, ''),
                            COALESCE(ni.idNumber, '')
                        )", ":fulltext"),
                    $qb->expr()->in('l.parentLocation', $qbl1->getDQL()),
                    $qb->expr()->in('l.parentLocation', $qbl2->getDQL()),
                    $qb->expr()->in('l.parentLocation', $qbl3->getDQL())
                ))
                ->setParameter('fulltext', '%'.$filter->getFulltext().'%');

            foreach ($qbl1->getParameters() as $parameter) {
                $qb->setParameter($parameter->getName(), $parameter->getValue());
            }
            foreach ($qbl2->getParameters() as $parameter) {
                $qb->setParameter($parameter->getName(), $parameter->getValue());
            }
            foreach ($qbl3->getParameters() as $parameter) {
                $qb->setParameter($parameter->getName(), $parameter->getValue());
            }
        }

        if ($filter->hasGender()) {
            $qb->andWhere('per.gender = :gender')
                ->setParameter('gender', 'M' === $filter->getGender() ? 1 : 0);
        }

        if ($filter->hasGender()) {
            $qb->andWhere('per.gender = :gender')
                ->setParameter('gender', 'M' === $filter->getGender() ? 1 : 0);
        }

        if ($filter->hasProjects()) {
            $qb->andWhere('p.id IN (:projects)')
                ->setParameter('projects', $filter->getProjects());
        }

        if ($filter->hasVulnerabilities()) {
            $qb->andWhere('vb.fieldString IN (:vulnerabilities)')
                ->setParameter('vulnerabilities', $filter->getVulnerabilities());
        }

        if ($filter->hasNationalIds()) {
            $qb->andWhere('ni.id IN (:nationalIds)')
                ->setParameter('nationalIds', $filter->getNationalIds());
        }

        if ($filter->hasResidencyStatuses()) {
            $qb->andWhere('b.residencyStatus IN (:residencyStatuses)')
                ->setParameter('residencyStatuses', $filter->getResidencyStatuses());
        }

        if ($filter->hasReferralTypes()) {
            $qb->andWhere('r.type IN (:referrals)')
                ->setParameter('referrals', $filter->getReferralTypes());
        }

        if ($filter->hasLivelihoods()) {
            
            $livelihoods = array_values(array_map(static function ($livelihood) {
                return LivelihoodEnum::valueToDB($livelihood);
            }, $filter->getLivelihoods()));
            
            $qb->andWhere('hh.livelihood IN (:livelihoods)')
                ->setParameter('livelihoods', $livelihoods);
        }

        if ($filter->hasLocations()) {
            $qb->andWhere('l.id  IN (:locations)')
                ->setParameter('locations', $filter->getLocations());
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                switch ($name) {
                    case HouseholdOrderInputType::SORT_BY_CURRENT_HOUSEHOLD_LOCATION:
                        $qb->addGroupBy('l.id')->addOrderBy('l.name', $direction);
                        break;
                    case HouseholdOrderInputType::SORT_BY_LOCAL_FIRST_NAME:
                        $qb->addGroupBy('headper.localGivenName')->addOrderBy('headper.localGivenName', $direction);
                        break;
                    case HouseholdOrderInputType::SORT_BY_LOCAL_FAMILY_NAME:
                        $qb->addGroupBy('headper.localFamilyName')->addOrderBy('headper.localFamilyName', $direction);
                        break;
                    case HouseholdOrderInputType::SORT_BY_DEPENDENTS:
                        $qb->addOrderBy('COUNT(DISTINCT b)', $direction);
                        break;
                    case HouseholdOrderInputType::SORT_BY_PROJECTS:
                        $qb->addGroupBy('p')->addOrderBy('p.name', $direction);
                        break;
                    case HouseholdOrderInputType::SORT_BY_VULNERABILITIES:
                        $qb->addGroupBy('vb')->addOrderBy('vb.fieldString', $direction);
                        break;
                    case HouseholdOrderInputType::SORT_BY_NATIONAL_ID:
                        $qb->addGroupBy('ni')->addOrderBy('ni.idNumber', $direction);
                        break;
                    case HouseholdOrderInputType::SORT_BY_ID:
                        $qb->addOrderBy('hh.id', $direction);
                        break;
                }
            }

            $qb->addGroupBy('hh.id');
        }

        return new Paginator($qb, false);
    }

    /**
     * Get all Household by country and id
     * @param string $iso3
     * @param array  $ids
     * @return mixed
     */
    public function getAllByIds(array $ids)
    {
        $qb = $this
            ->createQueryBuilder("hh")
            ->addSelect(['beneficiaries', 'projects', 'location', 'specificAnswers'])
            ->leftJoin('hh.beneficiaries', 'beneficiaries')
            ->leftJoin('hh.projects', 'projects')
            ->leftJoin('hh.householdLocations', 'location')
            ->leftJoin('hh.countrySpecificAnswers', 'specificAnswers')
            ->andWhere('hh.archived = 0')
            ->andWhere('hh.id IN (:ids)')
                ->setParameter('ids', $ids);

        return $qb->getQuery()->getResult();
    }

    /**
     *
     */
    public function getByHeadAndLocation(
        string $givenName,
        string $familyName,
        string $locationType,
        string $street = null,
        string $number = null,
        string $tentNumber = null
    ) {
        $qb = $this->createQueryBuilder('hh')
            ->select('hh')
            ->innerJoin('hh.beneficiaries', 'b')
            ->innerJoin('hh.householdLocations', 'hl')
            ->innerJoin('b.person', 'p')
            ->where('hh.archived = 0')
            ->andWhere('b.status = 1')
            ->andWhere('p.localGivenName = :givenName')
                ->setParameter('givenName', $givenName)
            ->andWhere('p.localFamilyName = :familyName')
                ->setParameter('familyName', $familyName)
        ;

        if ($locationType === HouseholdLocation::LOCATION_TYPE_CAMP) {
            $qb
                ->leftJoin('hl.campAddress', 'ca')
                ->andWhere('ca.tentNumber = :tentNumber')
                    ->setParameter('tentNumber', $tentNumber)
            ;
        }
        else {
            $qb
                ->leftJoin('hl.address', 'ad')
                ->andWhere('ad.street = :street')
                    ->setParameter('street', $street)
                ->andWhere('ad.number = :number')
                    ->setParameter('number', $number)
            ;
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findAllByHeadIds(array $ids)
    {
        $qb = $this->createQueryBuilder('hh')
            ->select('hh')
            ->innerJoin('hh.beneficiaries', 'b')
            ->where('hh.archived = 0')
            ->andWhere('b.status = 1')
            ->andWhere('b.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $onlyCount
     * @param $countryISO3
     * @param Project $project
     * @return QueryBuilder
     */
    public function configurationQueryBuilder($onlyCount, $countryISO3, Project $project = null)
    {
        $qb = $this->createQueryBuilder("hh");
        if ($onlyCount) {
            $qb->select("count(hh)");
        }

        if (null !== $project) {
            $qb->where(":idProject MEMBER OF hh.projects")
                ->setParameter("idProject", $project->getId());
        }
        $qb->leftJoin("hh.beneficiaries", "b");
        $this->setCountry($qb, $countryISO3);

        return $qb;
    }

    /**
     * Create sub request. The main request while found household inside the subrequest (and others subrequest)
     * The household must have at least one beneficiary with the condition respected ($field $operator $value / Example: gender = 0)
     *
     * @param QueryBuilder $qb
     * @param $i
     * @param $countryISO3
     * @param array $filters
     */
    public function whereDefault(QueryBuilder &$qb, $i, $countryISO3, array $filters)
    {
        $qbSub = $this->createQueryBuilder("hh$i");
        $this->setCountry($qbSub, $countryISO3, $i);
        $qbSub->leftJoin("hh$i.beneficiaries", "b$i")
            ->andWhere("b$i.{$filters["field_string"]} {$filters["condition_string"]} :val$i")
            ->setParameter("val$i", $filters["value_string"]);
        if (null !== $filters["target"]) {
            $qbSub->andWhere("b$i.status = :status$i")
                ->setParameter("status$i", $filters["target"]);
        }

        $qb->andWhere($qb->expr()->in("hh", $qbSub->getDQL()))
            ->setParameter("val$i", $filters["value_string"]);
        if (null !== $filters["target"]) {
            $qb->setParameter("status$i", $filters["target"]);
        }
    }

    /**
     * Create sub request to get location from household
     *
     * @param QueryBuilder $qb
     */
    protected function getHouseholdLocation(QueryBuilder &$qb)
    {
        $qb->leftJoin("hh.householdLocations", "hl")
            ->leftJoin("hl.campAddress", "ca")
            ->leftJoin("ca.camp", "c")
            ->leftJoin("hl.address", "ad")
            ->leftJoin(Location::class, "l", Join::WITH, "l.id = COALESCE(IDENTITY(c.location, 'id'), IDENTITY(ad.location, 'id'))");
    }

    /**
     * Create sub request to get households in country.
     * The household address location must be in the country ($countryISO3).
     *
     * @param QueryBuilder $qb
     * @param $countryISO3
     */
    public function whereHouseholdInCountry(QueryBuilder &$qb, $countryISO3)
    {
        $this->getHouseholdLocation($qb);
        $locationRepository = $this->getEntityManager()->getRepository(Location::class);
        $locationRepository->whereCountry($qb, $countryISO3);
    }

    /**
     * Create sub request to get the country of the household
     *
     * @param QueryBuilder $qb
     */
    public function getHouseholdCountry(QueryBuilder &$qb)
    {
        $this->getHouseholdLocation($qb);
        $locationRepository = $this->getEntityManager()->getRepository(Location::class);
        $locationRepository->getCountry($qb);
    }
}
