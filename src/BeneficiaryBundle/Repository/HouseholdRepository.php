<?php

namespace BeneficiaryBundle\Repository;

use DistributionBundle\Repository\AbstractCriteriaRepository;
use Doctrine\ORM\QueryBuilder;
use ProjectBundle\Entity\Project;

/**
 * HouseholdRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class HouseholdRepository extends AbstractCriteriaRepository
{

    /**
     * Return households which a Levenshtein distance with the stringToSearch under minimumTolerance
     * TODO : FOUND SOLUTION TO RETURN ONLY THE SIMILAR IF DISTANCE = 0 OR THE LIST OF HOUSEHOLDS WITH A DISTANCE
     * TODO : UNDER MINIMUMTOLERANCE, IF NO ONE HAS A DISTANCE = 0
     * @param string $stringToSearch
     * @param int $minimumTolerance
     * @return mixed
     */
    public function foundSimilarLevenshtein(string $stringToSearch, int $minimumTolerance)
    {
        $qb = $this->createQueryBuilder("hh");
        $q = $qb->leftJoin("hh.beneficiaries", "b")
            ->select("hh as household")
            ->addSelect("LEVENSHTEIN(
                    CONCAT(hh.addressStreet, hh.addressNumber, hh.addressPostcode, b.givenName, b.familyName),
                    :stringToSearch
                ) as levenshtein")
            ->where("b.status = 1")
            ->andWhere("
                LEVENSHTEIN(
                    CONCAT(hh.addressStreet, hh.addressNumber, hh.addressPostcode, b.givenName, b.familyName),
                    :stringToSearch
                ) < 
                CASE 
                    WHEN (LEVENSHTEIN(
                        CONCAT(hh.addressStreet, hh.addressNumber, hh.addressPostcode, b.givenName, b.familyName),
                        :stringToSearch) = 0) 
                        THEN 1
                    ELSE
                        :minimumTolerance
                    END
            ")
            ->setParameter("stringToSearch", $stringToSearch)
            ->setParameter("minimumTolerance", $minimumTolerance)
            ->orderBy("levenshtein", "ASC");

        return $q->getQuery()->getResult();
    }

    /**
     * Get all Household by country
     * Use $filters to add a offset and a limit. Default => offset = 0 and limit = 10
     * @param $iso3
     * @param $begin
     * @param $pageSize
     * @param $sort
     * @param array $filters
     * @return mixed
     */
    public function getAllBy($iso3, $begin, $pageSize, $sort, $filters = [])
    {
        $qb = $this->createQueryBuilder("hh");
        $q = $qb->leftJoin("hh.location", "l")
            ->leftJoin("l.adm1", "adm1")
            ->leftJoin("l.adm2", "adm2")
            ->leftJoin("l.adm3", "adm3")
            ->leftJoin("l.adm4", "adm4")
            ->where("adm1.countryISO3 = :iso3 AND hh.archived = 0")
            ->leftJoin("adm4.adm3", "adm3b")
            ->leftJoin("adm3b.adm2", "adm2b")
            ->leftJoin("adm2b.adm1", "adm1b")
            ->orWhere("adm1b.countryISO3 = :iso3 AND hh.archived = 0")
            ->leftJoin("adm3.adm2", "adm2c")
            ->leftJoin("adm2c.adm1", "adm1c")
            ->orWhere("adm1c.countryISO3 = :iso3 AND hh.archived = 0")
            ->leftJoin("adm2.adm1", "adm1d")
            ->orWhere("adm1d.countryISO3 = :iso3 AND hh.archived = 0")
            ->setParameter("iso3", $iso3);

        if (array_key_exists('sort', $sort) && array_key_exists('direction', $sort)) {
            $value = $sort['sort'];
            $direction = $sort['direction'];

            if ($value == 'location') {
                $q->addSelect("(COALESCE(adm4.name, adm3.name, adm2.name, adm1.name)) AS HIDDEN order_adm");
                $q->addOrderBy("order_adm", $direction)
                    ->addGroupBy("order_adm")
                    ->addGroupBy('hh.id');
            }
            else if ($value == 'firstName') {
                $q->leftJoin('hh.beneficiaries', 'b')
                    ->andWhere('hh.id = b.household')
                    ->addOrderBy('b.givenName', $direction)
                    ->addGroupBy("b.givenName")
                    ->addGroupBy('hh.id');
            }
            else if ($value == 'familyName') {
                $q->leftJoin('hh.beneficiaries', 'b')
                    ->andWhere('hh.id = b.household')
                    ->addOrderBy('b.familyName', $direction)
                    ->addGroupBy("b.familyName")
                    ->addGroupBy('hh.id');
            }
            else if ($value == 'dependents') {
                $q->leftJoin("hh.beneficiaries", 'b')
                    ->andWhere('hh.id = b.household')
                    ->addSelect('COUNT(b.household) AS HIDDEN countBenef')
                    ->addGroupBy('b.household')
                    ->addOrderBy('countBenef', $direction)
                    ->addGroupBy('hh.id');
            }
            else if ($value == 'projects') {
                $q->leftJoin('hh.projects', 'p')
                    ->addOrderBy('p.name', $direction)
                    ->addGroupBy("p.name")
                    ->addGroupBy('hh.id');
            }
            else if ($value == 'vulnerabilities') {
                $q->leftJoin('hh.beneficiaries', 'b')
                    ->andWhere('hh.id = b.household')
                    ->leftJoin('b.vulnerabilityCriteria', 'vb')
                    ->addOrderBy('vb.fieldString', $direction)
                    ->addGroupBy("vb.fieldString")
                    ->addGroupBy('hh.id');
            }
        }

        if (array_key_exists('filter', $filters)) {

            if ($filters['filter'] != '') {
                $filtered = $filters['filtered'];
                $filter = $filters['filter'];

                if ($filtered == 'location') {
                    $q->andWhere("adm4.name LIKE :filter")
                        ->orWhere("adm3.name LIKE :filter")
                        ->orWhere("adm2.name LIKE :filter")
                        ->orWhere("adm1.name LIKE :filter")
                        ->setParameter('filter', '%' . $filter . '%');
                } else if ($filtered == 'firstName') {
                    $q->leftJoin('hh.beneficiaries', 'b2')
                        ->andWhere('hh.id = b2.household')
                        ->andWhere('b2.givenName LIKE :filter')
                        ->setParameter('filter', '%' . $filter . '%');
                } else if ($filtered == 'familyName') {
                    $q->leftJoin('hh.beneficiaries', 'b2')
                        ->andWhere('hh.id = b2.household')
                        ->andWhere('b2.familyName LIKE :filter')
                        ->setParameter('filter', '%' . $filter . '%');
                } else if ($filtered == 'dependents') {
                    $q->leftJoin("hh.beneficiaries", 'b2')
                        ->andWhere('hh.id = b2.household')
                        ->andHaving('COUNT(b2.household) = :filter')
                        ->addGroupBy('b2.household')
                        ->setParameter('filter', $filter + 1);
                } else if ($filtered == 'projects') {
                    $q->leftJoin('hh.projects', 'p2')
                        ->andWhere('p2.name LIKE :filter')
                        ->setParameter('filter', '%' . $filter . '%');
                } else if ($filtered == 'vulnerabilities') {
                    $q->leftJoin('hh.beneficiaries', 'b2')
                        ->andWhere('hh.id = b2.household')
                        ->leftJoin('b2.vulnerabilityCriteria', 'vb2')
                        ->andWhere('vb2.fieldString LIKE :filter')
                        ->setParameter('filter', '%' . $filter . '%');
                }
            }
        }
        $allData = $q->getQuery()->getResult();

        $q->setFirstResult($begin)
            ->setMaxResults($pageSize);

        return [$allData, $q->getQuery()->getResult()];
    }

    /**
     * Get all Household by country and id
     * @param string $iso3
     * @param array  $ids
     * @return mixed
     */
    public function getAllByIds(string $iso3, array $ids)
    {
        $qb = $this->createQueryBuilder("hh");
        $q = $qb->leftJoin("hh.location", "l")
            ->leftJoin("l.adm1", "adm1")
            ->leftJoin("l.adm2", "adm2")
            ->leftJoin("l.adm3", "adm3")
            ->leftJoin("l.adm4", "adm4")
            ->where("adm1.countryISO3 = :iso3 AND hh.archived = 0")
            ->leftJoin("adm4.adm3", "adm3b")
            ->leftJoin("adm3b.adm2", "adm2b")
            ->leftJoin("adm2b.adm1", "adm1b")
            ->orWhere("adm1b.countryISO3 = :iso3 AND hh.archived = 0")
            ->leftJoin("adm3.adm2", "adm2c")
            ->leftJoin("adm2c.adm1", "adm1c")
            ->orWhere("adm1c.countryISO3 = :iso3 AND hh.archived = 0")
            ->leftJoin("adm2.adm1", "adm1d")
            ->orWhere("adm1d.countryISO3 = :iso3 AND hh.archived = 0")
            ->setParameter("iso3", $iso3);
        
        $q = $q->andWhere("hh.id IN (:ids)")
                ->setParameter("ids", $ids);

        return $q->getQuery()->getResult();
    }

    /**
     * @param $onlyCount
     * @param $countryISO3
     * @param Project $project
     * @return QueryBuilder|void
     */
    public function configurationQueryBuilder($onlyCount, $countryISO3, Project $project = null)
    {
        $qb = $this->createQueryBuilder("hh");
        if ($onlyCount)
            $qb->select("count(hh)");

        if (null !== $project)
        {
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
        if (null !== $filters["kind_beneficiary"])
            $qbSub->andWhere("b$i.status = :status$i")
                ->setParameter("status$i", $filters["kind_beneficiary"]);

        $qb->andWhere($qb->expr()->in("hh", $qbSub->getDQL()))
            ->setParameter("val$i", $filters["value_string"]);
        if (null !== $filters["kind_beneficiary"])
            $qb->setParameter("status$i", $filters["kind_beneficiary"]);
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
        if (boolval($filters["condition_string"]))
        {
            $qbSub->leftJoin("b$i.vulnerabilityCriteria", "vc$i")
                ->andWhere("vc$i.id = :idvc$i")
                ->setParameter("idvc$i", $filters["id_field"]);
        }
        else
        {
            $qbSubNotIn = $this->createQueryBuilder("hhb$i");
            $this->setCountry($qbSubNotIn, $countryISO3, "b$i");
            $qbSubNotIn->leftJoin("hhb$i.beneficiaries", "bb$i")
                ->leftJoin("bb$i.vulnerabilityCriteria", "vcb$i")
                ->andWhere("vcb$i.id = :idvc$i")
                ->setParameter("idvc$i", $filters["id_field"]);

            $qbSub->andWhere($qbSub->expr()->notIn("hh$i", $qbSubNotIn->getDQL()));
        }

        if (null !== $filters["kind_beneficiary"])
        {
            $qbSub->andWhere("b$i.status = :status$i")
                ->setParameter("status$i", $filters["kind_beneficiary"]);
        }

        $qb->andWhere($qb->expr()->in("hh", $qbSub->getDQL()))
            ->setParameter("idvc$i", $filters["id_field"])
            ->setParameter("status$i", $filters["kind_beneficiary"]);
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
     * count the number of housholds linked to a project
     *
     * @param Project $project
     * @return
     */
    public function countByProject(Project $project)
    {
        $qb = $this->createQueryBuilder("hh");
        $qb->select("count(hh)")
            ->leftJoin("hh.projects", "p")
            ->andWhere("p = :project")
            ->setParameter("project", $project);

        return $qb->getQuery()->getResult()[0];
    }
}
