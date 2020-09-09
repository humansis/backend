<?php

namespace BeneficiaryBundle\Repository;

use BeneficiaryBundle\Entity\HouseholdLocation;
use DistributionBundle\Repository\AbstractCriteriaRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
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
        $query->useResultCache(true,3600);

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
        $query->useResultCache(true,3600);

        return $query->getResult();
    }




    /**
     * Get all Household by country
     * @param $iso3
     * @param $begin
     * @param $pageSize
     * @param $sort
     * @param array $filters
     * @return mixed
     */
    public function getAllBy($iso3, $begin, $pageSize, $sort, $filters = [])
    {
        // Recover global information for the page
        $qb = $this->createQueryBuilder("hh");

        // Join all location tables (not just the one in the location)
        $this->whereHouseholdInCountry($qb, $iso3);

        // We join information that is needed for the filters
        $q = $qb->leftJoin("hh.beneficiaries", "b")
                ->leftJoin("hh.projects", "p")
                ->leftJoin("b.vulnerabilityCriteria", "vb")
                ->leftJoin("b.person", "per")
                ->leftJoin("per.nationalIds", "ni")
                ->leftJoin("per.referral", "r")
                ->leftJoin("hh.beneficiaries", "head")
                ->andWhere("head.status = 1")
                ->andWhere("hh.archived = 0");

        // If there is a sort, we recover the direction of the sort and the field that we want to sort
        if (array_key_exists("sort", $sort) && array_key_exists("direction", $sort)) {
            $value = $sort["sort"];
            $direction = $sort["direction"];

            // If the field is the location, we sort it by the direction sent
            if ($value == "currentHouseholdLocation") {
                $q->addGroupBy("adm1")->addOrderBy("adm1.name", $direction);
            }
            // If the field is the local first name, we sort it by the direction sent
            elseif ($value == "localFirstName") {
                $q->addGroupBy("head.localGivenName")->addOrderBy("head.localGivenName", $direction);
            }
            // If the field is the local family name, we sort it by the direction sent
            elseif ($value == "localFamilyName") {
                $q->addGroupBy("head.localFamilyName")->addOrderBy("head.localFamilyName", $direction);
            }
            // If the field is the number of dependents, we sort it by the direction sent
            elseif ($value == "dependents") {
                $q->addOrderBy("COUNT(DISTINCT b)", $direction);
            }
            // If the field is the projects, we sort it by the direction sent
            elseif ($value == "projects") {
                $q->addGroupBy("p")->addOrderBy("p.name", $direction);
            }
            // If the field is the vulnerabilities, we sort it by the direction sent
            elseif ($value == "vulnerabilities") {
                $q->addGroupBy("vb")->addOrderBy("vb.fieldString", $direction);
            }
            // If the field is the national ID, we sort it by the direction sent
            elseif ($value == "nationalId") {
                $q->addGroupBy("ni")->addOrderBy("ni.idNumber", $direction);
            } elseif ($value == "id") {
                $q->addOrderBy("hh.id", $direction);
            }

            $q->addGroupBy("hh.id");
        }

        // If there is a filter array in the request
        if (count($filters) > 0) {
            // For each filter in our array, we recover an index (to avoid parameters' repetitions in the WHERE clause) and the filters
            foreach ($filters as $indexFilter => $filter) {
                // We recover the category of the filter chosen and the value of the filter
                $category = $filter["category"];
                $filterValues = $filter["filter"];

                if ($category === "any" && count($filterValues) > 0) {
                    foreach ($filterValues as $filterValue) {
                        $q->andWhere("CONCAT(
                            COALESCE(b.enFamilyName, ''),
                            COALESCE(b.enGivenName, ''),
                            COALESCE(b.localFamilyName, ''),
                            COALESCE(b.localGivenName, ''),
                            COALESCE(p.name, ''),
                            COALESCE(adm1.name, ''),
                            COALESCE(adm2.name, ''),
                            COALESCE(adm3.name, ''),
                            COALESCE(adm4.name, ''),
                            COALESCE(vb.fieldString, ''),
                            COALESCE(ni.idNumber, '')
                        ) LIKE '%" . $filterValue . "%'");
                    }
                } elseif ($category === "gender") {
                    // If the category is the gender only one option can be selected and filterValues is a string instead of an array
                    $q->andWhere("b.gender = :filterValue")
                        ->setParameter("filterValue", $filterValues);
                } elseif ($category === "projects" && count($filterValues) > 0) {
                    $orStatement = $q->expr()->orX();
                    foreach ($filterValues as $indexValue => $filterValue) {
                        $q->setParameter("filter" . $indexFilter . $indexValue, $filterValue);
                        $orStatement->add($q->expr()->eq("p.id", ":filter" . $indexFilter . $indexValue));
                    }
                    $q->andWhere($orStatement);
                } elseif ($category === "vulnerabilities" && count($filterValues) > 0) {
                    $orStatement = $q->expr()->orX();
                    foreach ($filterValues as $indexValue => $filterValue) {
                        $q->setParameter("filter" . $indexFilter . $indexValue, $filterValue);
                        $orStatement->add($q->expr()->eq("vb.id", ":filter" . $indexFilter . $indexValue));
                    }
                    $q->andWhere($orStatement);
                } elseif ($category === "nationalId" && count($filterValues) > 0) {
                    $orStatement = $q->expr()->orX();
                    foreach ($filterValues as $indexValue => $filterValue) {
                        $q->setParameter("filter" . $indexFilter . $indexValue, $filterValue);
                        $orStatement->add($q->expr()->eq("ni.id", ":filter" . $indexFilter . $indexValue));
                    }
                    $q->andWhere($orStatement);
                } elseif ($category === "residency" && count($filterValues) > 0) {
                    $orStatement = $q->expr()->orX();
                    foreach ($filterValues as $indexValue => $filterValue) {
                        $q->setParameter("filter" . $indexFilter . $indexValue, $filterValue);
                        $orStatement->add($q->expr()->eq("b.residencyStatus", ":filter" . $indexFilter . $indexValue));
                    }
                    $q->andWhere($orStatement);
                } elseif ($category === "referral" && count($filterValues) > 0) {
                    $orStatement = $q->expr()->orX();
                    foreach ($filterValues as $indexValue => $filterValue) {
                        $q->setParameter("filter" . $indexFilter . $indexValue, $filterValue);
                        $orStatement->add($q->expr()->eq("r.type", ":filter" . $indexFilter . $indexValue));
                    }
                    $q->andWhere($orStatement);
                }
                elseif ($category === "livelihood" && count($filterValues) > 0) {
                    $orStatement = $q->expr()->orX();
                    foreach ($filterValues as $indexValue => $filterValue) {
                        $q->setParameter("filter" . $indexFilter . $indexValue, $filterValue);
                        $orStatement->add($q->expr()->eq("hh.livelihood", ":filter" . $indexFilter . $indexValue));
                    }
                    $q->andWhere($orStatement);
                } elseif ($category === "locations") {
                    // If the category is the location, filterValues is an array of adm ids
                    foreach ($filterValues as $adm => $id) {
                        $q->andWhere($adm . " = :id" . $indexFilter)
                            ->setParameter("id" . $indexFilter, $id);
                    }
                }
            }
        }

        if (is_null($begin)) {
            $begin = 0;
        }
        if (is_null($pageSize)) {
            $pageSize = 0;
        }

        if ($pageSize > -1) {
            $q->setFirstResult($begin)
            ->setMaxResults($pageSize);
        }

        $paginator = new Paginator($q, $fetchJoinCellection = true);

        $query = $q->getQuery();
        $query->useResultCache(true,3600);

        return [count($paginator), $query->getResult()];
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

        return $qb->getQuery()->useResultCache(true, 600)->getOneOrNullResult();
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
     * Create sub request. The main request while found household inside the subrequest (and others subrequest)
     * The household must respect the value of the country specific ($idCountrySpecific), depends on operator and value
     *
     * @param QueryBuilder $qb
     * @param $i
     * @param $countryISO3
     * @param array $filters
     */
    protected function whereVulnerabilityCriterion(QueryBuilder &$qb, $i, $countryISO3, array $filters)
    {
        $qbSub = $this->createQueryBuilder("hh$i");
        $this->setCountry($qbSub, $countryISO3, $i);
        $qbSub->leftJoin("hh$i.beneficiaries", "b$i");
        if (boolval($filters["condition_string"])) {
            $qbSub->leftJoin("b$i.vulnerabilityCriteria", "vc$i")
                ->andWhere("vc$i.id = :idvc$i")
                ->setParameter("idvc$i", $filters["id_field"]);
        } else {
            $qbSubNotIn = $this->createQueryBuilder("hhb$i");
            $this->setCountry($qbSubNotIn, $countryISO3, "b$i");
            $qbSubNotIn->leftJoin("hhb$i.beneficiaries", "bb$i")
                ->leftJoin("bb$i.vulnerabilityCriteria", "vcb$i")
                ->andWhere("vcb$i.id = :idvc$i")
                ->setParameter("idvc$i", $filters["id_field"]);

            $qbSub->andWhere($qbSub->expr()->notIn("hh$i", $qbSubNotIn->getDQL()));
        }

        if (null !== $filters["target"]) {
            $qbSub->andWhere("b$i.status = :status$i")
                ->setParameter("status$i", $filters["target"]);
        }

        $qb->andWhere($qb->expr()->in("hh", $qbSub->getDQL()))
            ->setParameter("idvc$i", $filters["id_field"])
            ->setParameter("status$i", $filters["target"]);
    }

    /**
     * Create sub request. The main request while found household inside the subrequest (and others subrequest)
     * The household must respect the value of the country specific ($idCountrySpecific), depends on operator and value
     *
     * @param QueryBuilder $qb
     * @param $i
     * @param $countryISO3
     * @param array $filters
     */
    protected function whereCountrySpecific(QueryBuilder &$qb, $i, $countryISO3, array $filters)
    {
        $qbSub = $this->createQueryBuilder("hh$i");
        $this->setCountry($qbSub, $countryISO3, $i);
        $qbSub->leftJoin("hh$i.countrySpecificAnswers", "csa$i")
            ->andWhere("csa$i.countrySpecific = :countrySpecific$i")
            ->setParameter("countrySpecific$i", $filters["id_field"])
            ->andWhere("csa$i.answer {$filters["condition_string"]} :value$i")
            ->setParameter("value$i", $filters["value_string"]);

        $qb->andWhere($qb->expr()->in("hh", $qbSub->getDQL()))
            ->setParameter("value$i", $filters["value_string"])
            ->setParameter("countrySpecific$i", $filters["id_field"]);
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
