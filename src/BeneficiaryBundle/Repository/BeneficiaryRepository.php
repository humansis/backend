<?php

namespace BeneficiaryBundle\Repository;

use BeneficiaryBundle\Entity\Household;
use DistributionBundle\Entity\DistributionData;
use CommonBundle\Entity\Location;
use DistributionBundle\Repository\AbstractCriteriaRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use ProjectBundle\Entity\Project;

/**
 * BeneficiaryRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BeneficiaryRepository extends AbstractCriteriaRepository
{
    /**
     * Get all beneficiaries in a selected project.
     *
     * @param int $project
     *
     * @param string $target
     * @return mixed
     */
    public function getAllOfProject(int $project, string $target)
    {
        $qb = $this->createQueryBuilder('b');
        if ($target == 'Household') {
            $q = $qb->leftJoin('b.household', 'hh')
                ->where(':project MEMBER OF hh.projects')
                ->andWhere('b.status = 1')
                ->setParameter('project', $project);
        } else {
            $q = $qb->leftJoin('b.household', 'hh')
                ->where(':project MEMBER OF hh.projects')
                ->setParameter('project', $project);
        }

        return $q->getQuery()->getResult();
    }

    public function findByUnarchived(array $byArray)
    {
        $qb = $this->createQueryBuilder('b');
        $q = $qb->leftJoin('b.household', 'hh')
                ->where('hh.archived = 0');
        foreach ($byArray as $key => $value) {
            $q = $q->andWhere('b.' . $key . ' = :value' . $key)
                    ->setParameter('value' . $key, $value);
        }

        return $q->getQuery()->getResult();
    }

    /**
     * @param string $value
     * @param string $conditionString
     * @param int $beneficiaryId
     * @return mixed
     */
    public function hasDateOfBirth(string $value, string $conditionString, int $beneficiaryId)
    {
        $qb = $this->createQueryBuilder('b');

        $q  = $qb->where('b.dateOfBirth ' . $conditionString . ' :value')
            ->setParameter('value', $value)
            ->andWhere('b.id = :beneficiaryId')
            ->setParameter('beneficiaryId', $beneficiaryId);

        return $q->getQuery()->getResult();
    }

    /**
     * @param int $vulnerabilityId
     * @param string $conditionString
     * @param int $beneficiaryId
     * @return mixed
     */
    public function hasVulnerabilityCriterion(int $vulnerabilityId, string $conditionString, int $beneficiaryId)
    {
        $qb = $this->createQueryBuilder('b');

        if ($conditionString == "true") {
            $q = $qb->leftJoin('b.vulnerabilityCriteria', 'vc')
                ->where(':vulnerabilityId = vc.id')
                ->setParameter('vulnerabilityId', $vulnerabilityId)
                ->andWhere(':beneficiaryId = b.id')
                ->setParameter(':beneficiaryId', $beneficiaryId);
        } else {
            $q = $qb->leftJoin('b.vulnerabilityCriteria', 'vc')
                ->where(':vulnerabilityId <> vc.id')
                ->setParameter('vulnerabilityId', $vulnerabilityId)
                ->andWhere(':beneficiaryId = b.id')
                ->setParameter(':beneficiaryId', $beneficiaryId);
        }

        return $q->getQuery()->getResult();
    }

    /**
     * @param string $conditionString
     * @param string $valueString
     * @param int $beneficiaryId
     * @return mixed
     */
    public function hasGender(string $conditionString, string $valueString, int $beneficiaryId)
    {
        $qb = $this->createQueryBuilder('b');

        if ($conditionString == '=') {
            $q = $qb->where(':gender = b.gender')
                ->setParameter('gender', $valueString)
                ->andWhere(':beneficiaryId = b.id')
                ->setParameter(':beneficiaryId', $beneficiaryId);
        } else {
            $q = $qb->where(':gender <> b.gender')
                ->setParameter('gender', $valueString)
                ->andWhere(':beneficiaryId = b.id')
                ->setParameter(':beneficiaryId', $beneficiaryId);
        }

        return $q->getQuery()->getResult();
    }

    public function getAllInCountry(string $iso3) {
        $qb = $this->createQueryBuilder('b');
        $this->beneficiariesInCountry($qb, $iso3);
        $qb->andWhere('hh.archived = 0');

        return $qb->getQuery()->getResult();
    }

    public function countAllInCountry(string $iso3)
    {
        $qb = $this->createQueryBuilder('b');
        $this->beneficiariesInCountry($qb, $iso3);
        $qb->andWhere('hh.archived = 0')
            ->select('COUNT(b)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getAllofDistribution(DistributionData $distributionData)
    {
        $qb = $this->createQueryBuilder('b');
        $q = $qb->leftJoin('b.distributionBeneficiary', 'db')
            ->where('db.distributionData = :distributionData')
            ->setParameter('distributionData', $distributionData);

        return $q->getQuery()->getResult();
    }

    /**
     * Get the head of household.
     *
     * @param Household $household
     *
     * @return mixed
     */
    public function getHeadOfHousehold(Household $household)
    {
        $qb = $this->createQueryBuilder('b');
        $q = $qb->where('b.household = :household')
            ->andWhere('b.status = 1')
            ->setParameter('household', $household);

        try {
            return $q->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * Get the head of household.
     *
     * @param $householdId
     *
     * @return mixed
     */
    public function getHeadOfHouseholdId($householdId)
    {
        $qb = $this->createQueryBuilder('b');
        $q = $qb->leftJoin('b.household', 'hh')
            ->andWhere('hh.id = :id')
            ->andWhere('b.status = 1')
            ->setParameter('id', $householdId);

        try {
            return $q->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @param $onlyCount
     * @param $countryISO3
     * @param Project $project
     *
     * @return QueryBuilder|void
     */
    public function configurationQueryBuilder($onlyCount, $countryISO3, Project $project = null)
    {
        $qb = $this->createQueryBuilder('b');

        if ($onlyCount) {
            $qb->select('count(b)');
        }
        if (null !== $project) {
            $qb->where(':idProject MEMBER OF hh.projects')
                ->setParameter('idProject', $project->getId());
        }
        $qb->leftJoin('b.household', 'hh');
        $this->setCountry($qb, $countryISO3);

        return $qb;
    }

    /**
     * Create sub request. The main request while found household inside the subrequest (and others subrequest)
     * The household must have at least one beneficiary with the condition respected ($field $operator $value / Example: gender = 0).
     *
     * @param QueryBuilder $qb
     * @param $i
     * @param $countryISO3
     * @param array $filters
     */
    public function whereDefault(QueryBuilder &$qb, $i, $countryISO3, array $filters)
    {
        $qb->andWhere("b.{$filters['field_string']} {$filters['condition_string']} :val$i")
            ->setParameter("val$i", $filters['value_string']);
    }

    /**
     * Create sub request. The main request while found household inside the subrequest (and others subrequest)
     * The household must respect the value of the country specific ($idCountrySpecific), depends on operator and value.
     *
     * @param QueryBuilder $qb
     * @param $i
     * @param $countryISO3
     * @param array $filters
     */
    protected function whereVulnerabilityCriterion(QueryBuilder &$qb, $i, $countryISO3, array $filters)
    {
        $qb->leftJoin('b.vulnerabilityCriteria', "vc$i")
            ->andWhere("vc$i.id = :idvc$i")
            ->setParameter("idvc$i", $filters['id_field']);
    }

    /**
     * Create sub request. The main request while found household inside the subrequest (and others subrequest)
     * The household must respect the value of the country specific ($idCountrySpecific), depends on operator and value.
     *
     * @param QueryBuilder $qb
     * @param $i
     * @param $countryISO3
     * @param array $filters
     */
    protected function whereCountrySpecific(QueryBuilder &$qb, $i, $countryISO3, array $filters)
    {
        $qb->leftJoin('hh.countrySpecificAnswers', "csa$i")
            ->andWhere("csa$i.countrySpecific = :countrySpecific$i")
            ->setParameter("countrySpecific$i", $filters['id_field'])
            ->andWhere("csa$i.answer {$filters['condition_string']} :value$i")
            ->setParameter("value$i", $filters['value_string']);
    }

    public function countServedInCountry($iso3) {
        $qb = $this->createQueryBuilder('b');
        $this->beneficiariesInCountry($qb, $iso3);

        $qb->select('COUNT(DISTINCT b)')
            ->leftJoin('b.distributionBeneficiary', 'db')
            ->leftJoin('db.booklets', 'bk')
            ->leftJoin('db.transactions', 't')
            ->leftJoin('db.generalReliefs', 'gri')
            ->andWhere('t.transactionStatus = 1 OR gri.distributedAt IS NOT NULL OR bk.id IS NOT NULL');

        return $qb->getQuery()->getSingleScalarResult();
    }

    private function beneficiariesInCountry(QueryBuilder &$qb, $countryISO3) {
        $qb->leftJoin('b.household', 'hh');

        $householdRepository = $this->getEntityManager()->getRepository(Household::class);
        $householdRepository->whereHouseholdInCountry($qb, $countryISO3);
    }
}
