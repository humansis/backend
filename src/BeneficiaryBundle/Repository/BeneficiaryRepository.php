<?php

namespace BeneficiaryBundle\Repository;

use BeneficiaryBundle\Entity\Household;
use DistributionBundle\Entity\Assistance;
use CommonBundle\Entity\Location;
use DistributionBundle\Enum\AssistanceTargetType;
use DistributionBundle\Repository\AbstractCriteriaRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\Component\Import\Identity\NationalIdHashSet;
use NewApiBundle\DBAL\PersonGenderEnum;
use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\NationalIdType;
use NewApiBundle\Enum\PersonGender;
use NewApiBundle\InputType\BeneficiaryFilterInputType;
use NewApiBundle\InputType\BeneficiaryOrderInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;
use Doctrine\ORM\Query\Expr\Join;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm1;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Enum\SmartcardStates;

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
     * @param int    $project
     * @param string $target
     *
     * @return mixed
     */
    public function getAllOfProject(int $project, string $target)
    {
        $qb = $this->createQueryBuilder('b');
        if (AssistanceTargetType::HOUSEHOLD === $target) {
            $q = $qb->leftJoin('b.household', 'hh')
                ->where(':project MEMBER OF hh.projects')
                ->andWhere('b.status = 1')
                ->andWhere('b.archived = 0')
                ->setParameter('project', $project);
        } elseif (AssistanceTargetType::INDIVIDUAL === $target) {
            $q = $qb->leftJoin('b.household', 'hh')
                ->andWhere(':project MEMBER OF hh.projects')
                ->andWhere('b.archived = 0')
                ->setParameter('project', $project);
        } else {
            return [];
        }

        return $q->getQuery()->getResult();
    }

    public function findByUnarchived(array $byArray)
    {
        $qb = $this->createQueryBuilder('b');
        $q = $qb->leftJoin('b.household', 'hh')
            ->where('hh.archived = 0');
        foreach ($byArray as $key => $value) {
            $q = $q->andWhere('b.'.$key.' = :value'.$key)
                ->setParameter('value'.$key, $value);
        }

        return $q->getQuery()->getResult();
    }

    /**
     * @param Project $project
     *
     * @return QueryBuilder
     */
    public function getQbUnarchivedByProject(Project $project): QueryBuilder
    {
        $qb = $this->createQueryBuilder("bnf");

        return $qb->leftJoin("bnf.projects", "p")
            ->where("p = :project")
            ->setParameter("project", $project)
            ->andWhere("bnf.archived = 0");
    }

    /**
     * @param Project $project
     *
     * @return null|\DateTimeInterface
     * @throws NonUniqueResultException
     */
    public function getLastModifiedByProject(Project $project): ?\DateTimeInterface
    {
        $qb = $this->getQbUnarchivedByProject($project);
        $qb->select('bnf.updatedOn')
            ->orderBy('bnf.updatedOn', 'DESC')
            ->setFirstResult(0)
            ->setMaxResults(1);

        try {
            return $qb->getQuery()->getSingleResult(AbstractQuery::HYDRATE_ARRAY)['updatedOn'];
        } catch (NoResultException $e) {
            return null;
        }
    }

    public function getUnarchivedByProject(Project $project): iterable
    {
        $q = $this->getQbUnarchivedByProject($project);

        return $q->getQuery()->getResult();
    }

    public function findByName(string $givenName, ?string $parentsName, string $familyName, ?string $gender = null, Household $household = null)
    {
        $qbr =  $this->createQueryBuilder('b')
            ->leftJoin('b.household', 'hh')
            ->join('b.person', 'p')
            ->andWhere('hh.archived = 0')
            ->andWhere('p.localGivenName = :givenName')
            ->andWhere('(p.localParentsName = :parentsName OR (p.localParentsName IS NULL AND :parentsName IS NULL) )')
            ->andWhere('p.localFamilyName = :familyName')
            ->setParameter('givenName', $givenName)
            ->setParameter('parentsName', $parentsName)
            ->setParameter('familyName', $familyName);

        if (null !== $gender) {
            $qbr
                ->andWhere('p.gender = :gender')
                ->setParameter('gender', PersonGenderEnum::valueToDB($gender));
        }

        if (!is_null($household)) {
            $qbr->andWhere('hh.id = :hhId')
                ->setParameter('hhId', $household->getId());
        }

        return $qbr->getQuery()
            ->getResult();
    }

    public function getAllInCountry(string $iso3)
    {
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
            ->select('COUNT(DISTINCT b)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Counts Household members in project.
     *
     * @param Project $project
     *
     * @return int
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countAllInProject(Project $project): int
    {
        $qb = $this->createQueryBuilder('b');
        $qb
            ->select('COUNT(DISTINCT b)')
            ->join('b.household', 'hh')
            ->where(':project MEMBER OF hh.projects')
            ->setParameter('project', $project)
            ->andWhere('b.archived = 0');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findIdentity(string $idType, string $idNumber, ?string $iso3 = null, ?Household $household = null)
    {
        $qb = $this->createQueryBuilder('b')
            ->join('b.person', 'p')
            ->join('b.household', 'hh')
            ->join('p.nationalIds', 'id')
            ->andWhere('b.archived = 0')
            ->andWhere('hh.archived = 0')
            ->andWhere('id.idType = :idType')
            ->andWhere('id.idNumber = :idNumber')
            ->setParameter('idNumber', $idNumber)
            ->setParameter('idType', $idType);

        if (null !== $iso3) {
            $qb->join('hh.projects', 'project')
                ->andWhere('project.iso3 = :country')
                ->setParameter('country', $iso3);
        }

        if (null !== $household) {
            $qb->andWhere('hh.id = :hhId')
                ->setParameter('hhId', $household->getId());
        }

        return $qb->getQuery()
            ->getResult();
    }

    public function findIdentitiesByNationalIds(string $iso3, NationalIdHashSet $ids)
    {
        $qb = $this->createQueryBuilder('b')
            ->join('b.person', 'p')
            ->join('b.household', 'hh')
            ->join('p.nationalIds', 'id')
            ->andWhere('b.archived = 0')
            ->andWhere('hh.archived = 0')
            ->andWhere('id.idNumber IN (:idNumbers)')
            ->andWhere('id.idType IN (:idTypes)')
            ->setParameter('idNumbers', $ids->getNumbers())
            ->setParameter('idTypes', $ids->getTypes());

        if (null !== $iso3) {
            $qb->join('hh.projects', 'project')
                ->andWhere('project.iso3 = :country')
                ->setParameter('country', $iso3);
        }

        return $qb->getQuery()
            ->getResult();
    }

    public function getAllofDistribution(Assistance $assistance)
    {
        $qb = $this->createQueryBuilder('b');
        $q = $qb->leftJoin('b.assistanceBeneficiary', 'db')
            ->where('db.assistance = :assistance')
            ->setParameter('assistance', $assistance);

        return $q->getQuery()->getResult();
    }

    public function getImported(Import $import)
    {
        $qb = $this->createQueryBuilder('b');
        $q = $qb->innerJoin('b.importBeneficiaries', 'ib')
            ->where('ib.import = :import')
            ->setParameter('import', $import);

        return $q->getQuery()->getResult();
    }

    public function getNotRemovedofDistribution(Assistance $assistance)
    {
        $qb = $this->createQueryBuilder('b');
        $q = $qb->leftJoin('b.assistanceBeneficiary', 'db')
            ->where('db.assistance = :assistance')
            ->andWhere('db.removed = 0')
            ->setParameter('assistance', $assistance);

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

    public function countByResidencyStatus(Assistance $assistance, string $residencyStatus): int
    {
        if (AssistanceTargetType::HOUSEHOLD === $assistance->getTargetType()) {
            $qb = $this->createQueryBuilder('hhm')
                ->select('COUNT(hhm)')
                ->join('hhm.household', 'h')
                ->join('h.beneficiaries', 'hhh')
                ->join('hhh.assistanceBeneficiary', 'db', 'WITH', 'db.removed=0')
                ->andWhere('db.assistance = :assistance')
                ->andWhere('hhm.residencyStatus = :residencyStatus')
                ->andWhere('hhm.archived = 0')
                ->setParameter('assistance', $assistance)
                ->setParameter('residencyStatus', $residencyStatus);
        } else {
            $qb = $this->createQueryBuilder('b')
                ->select('COUNT(DISTINCT b)')
                ->join('b.assistanceBeneficiary', 'db', 'WITH', 'db.removed=0')
                ->andWhere('db.assistance = :assistance')
                ->andWhere('b.residencyStatus = :residencyStatus')
                ->andWhere('b.archived = 0')
                ->setParameter('assistance', $assistance)
                ->setParameter('residencyStatus', $residencyStatus);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function countHouseholdHeadsByGender(Assistance $assistance, string $gender): int
    {
        $qb = $this->createQueryBuilder('b');
        $qb->select('COUNT(DISTINCT b)');
        $this->whereInDistribution($qb, $assistance);
        $this->whereHouseHoldHead($qb);
        $this->whereGender($qb, $gender);
        $qb->andWhere('b.archived = 0');
        return $qb->getQuery()->getSingleScalarResult();
    }

    public function countByAgeAndByGender(Assistance $distribution, int $gender, int $minAge, int $maxAge, \DateTimeInterface $distributionDate): int
    {
        $maxDateOfBirth = clone $distributionDate;
        $minDateOfBirth = clone $distributionDate;
        $maxDateOfBirth->sub(new \DateInterval('P'.$minAge.'Y'));
        $minDateOfBirth->sub(new \DateInterval('P'.$maxAge.'Y'));

        if (AssistanceTargetType::HOUSEHOLD === $distribution->getTargetType()) {
            $qb = $this->createQueryBuilder('hhm')
                ->select('COUNT(hhm)')
                ->join('hhm.person', 'p', 'WITH', 'p.gender = :g AND p.dateOfBirth >= :minDateOfBirth AND p.dateOfBirth < :maxDateOfBirth')
                ->join('hhm.household', 'h')
                ->join('h.beneficiaries', 'hhh')
                ->join('hhh.assistanceBeneficiary', 'db', 'WITH', 'db.removed=0')
                ->andWhere('db.assistance = :assistance')
                ->andWhere('hhm.archived = 0')
                ->setParameter('assistance', $distribution)
                ->setParameter('g', $gender)
                ->setParameter('minDateOfBirth', $minDateOfBirth)
                ->setParameter('maxDateOfBirth', $maxDateOfBirth);
        } else {
            $qb = $this->createQueryBuilder('b')
                ->select('COUNT(b)')
                ->join('b.person', 'p', 'WITH', 'p.gender = :g AND p.dateOfBirth >= :minDateOfBirth AND p.dateOfBirth < :maxDateOfBirth')
                ->join('b.distributionBeneficiaries', 'db', 'WITH', 'db.removed=0')
                ->andWhere('db.assistance = :assistance')
                ->andWhere('b.archived = 0')
                ->setParameter('assistance', $distribution)
                ->setParameter('g', $gender)
                ->setParameter('minDateOfBirth', $minDateOfBirth)
                ->setParameter('maxDateOfBirth', $maxDateOfBirth);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function countServed(Assistance $distribution, string $modalityType): int
    {
        $qb = $this->createQueryBuilder('b');
        $qb->select('COUNT(DISTINCT b)');
        $this->whereInDistribution($qb, $distribution);

        if ('Mobile Money' === $modalityType) {
            $qb->innerJoin('db.transactions', 't', Join::WITH, 't.transactionStatus = 1');
        } else if ($modalityType === 'QR Code Voucher') {
            $qb->innerJoin('db.booklets', 'bo', Join::WITH, 'bo.status = 1 OR bo.status = 2');
        } else {
            $qb->innerJoin('db.generalReliefs', 'gr', Join::WITH, 'gr.distributedAt IS NOT NULL');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param         $onlyCount
     * @param         $countryISO3
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

    protected function whereInDistribution(QueryBuilder $qb, Assistance $assistance)
    {
        if (!in_array('db', $qb->getAllAliases())) {
            $qb->leftJoin(
                'b.distributionBeneficiaries',
                'db',
                Join::WITH,
                'db.removed = 0'
            );
            $qb->andWhere('db.assistance = :distribution');
            $qb->setParameter('distribution', $assistance);
        }
    }

    protected function whereHouseHoldHead(QueryBuilder $qb)
    {
        $qb->andWhere('b.status = :status');
        $qb->setParameter('status', 1); // status = HHH
    }

    protected function whereGender(QueryBuilder $qb, string $gender)
    {
        if (!in_array('p', $qb->getAllAliases())) {
            $qb->join('b.person', 'p');
        }
        $qb->andWhere('p.gender = :g');
        $qb->setParameter('g', PersonGenderEnum::valueToDB($gender));
    }

    protected function whereBornBetween(QueryBuilder &$qb, \DateTimeInterface $minDateOfBirth, \DateTimeInterface $maxDateOfBirth)
    {
        if (!in_array('p', $qb->getAllAliases())) {
            $qb->join('b.person', 'p');
        }
        $qb->andWhere('p.dateOfBirth >= :minDateOfBirth');
        $qb->andWhere('p.dateOfBirth < :maxDateOfBirth');
        $qb->setParameter('minDateOfBirth', $minDateOfBirth);
        $qb->setParameter('maxDateOfBirth', $maxDateOfBirth);
    }

    /**
     * Create sub request. The main request while found household inside the subrequest (and others subrequest)
     * The household must have at least one beneficiary with the condition respected ($field $operator $value / Example: gender = 0).
     *
     * @param QueryBuilder $qb
     * @param              $i
     * @param              $countryISO3
     * @param array        $filters
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

    public function countServedInCountry($iso3)
    {
        $qb = $this->createQueryBuilder('b');
        $this->beneficiariesInCountry($qb, $iso3);

        $qb->select('COUNT(DISTINCT b)')
            ->leftJoin('b.assistanceBeneficiary', 'db')
            ->leftJoin('db.booklets', 'bk')
            ->leftJoin('db.transactions', 't')
            ->leftJoin('db.generalReliefs', 'gri')
            ->andWhere('t.transactionStatus = 1 OR gri.distributedAt IS NOT NULL OR bk.id IS NOT NULL');

        return $qb->getQuery()->getSingleScalarResult();
    }

    private function beneficiariesInCountry(QueryBuilder &$qb, $countryISO3)
    {
        $qb->leftJoin('b.household', 'hh');

        $householdRepository = $this->getEntityManager()->getRepository(Household::class);
        $householdRepository->whereHouseholdInCountry($qb, $countryISO3);
    }


    public function getDistributionBeneficiaries(array $criteria, Project $project)
    {
        $hhRepository = $this->getEntityManager()->getRepository(Household::class);
        $qb = $hhRepository->getUnarchivedByProject($project);

        // First we get all the beneficiaries, and we store the headId for later
        $qb->leftJoin('hh.beneficiaries', 'b')
            ->select('DISTINCT b.id AS id')
            ->leftJoin('hh.beneficiaries', 'head')
            ->andWhere('head.status = 1')
            ->addSelect('head.id AS headId');

        // If a beneficiary has a criterion, they are selectable, therefore every criterion has to go in a orX()
        $userConditionsStatement = $qb->expr()->andX();
        foreach ($criteria as $index => $criterion) {
            $condition = $criterion['condition_string'];
            $field = $criterion['field_string'];
            $condition = $condition === '!=' ? '<>' : $condition;

            if ('Household' == $criterion['target']) {
                $this->getHouseholdWithCriterion($qb, $field, $condition, $criterion, $index, $userConditionsStatement);
            } elseif ('Beneficiary' == $criterion['target']) {
                $this->getBeneficiaryWithCriterion($qb, $field, $condition, $criterion, $index, $userConditionsStatement);
            } elseif ('Head' == $criterion['target']) {
                $this->getHeadWithCriterion($qb, $field, $condition, $criterion, $index, $userConditionsStatement);
            }
            if (array_key_exists('value_string', $criterion) && !is_null($criterion['value_string'])) {
                $qb->setParameter('parameter'.$index, $criterion['value_string']);
            }
        }
        $qb->andWhere($userConditionsStatement);

        return $qb->getQuery()->getResult();
    }

    private function getHouseholdWithCriterion(&$qb, $field, $condition, $criterion, int $i, &$userConditionsStatement)
    {
        // The selection criteria is a country Specific
        if ($criterion['table_string'] === 'countrySpecific') {
            $qb->leftJoin('hh.countrySpecificAnswers', 'csa'. $i, Join::WITH, 'csa'.$i . '.answer ' . $condition . ' :parameter'.$i)
            ->leftJoin('csa'.$i . '.countrySpecific', 'cs'.$i, Join::WITH, 'cs'.$i . '.fieldString = :csName'.$i)
            ->setParameter('csName'.$i, $field);

            // To validate the criterion, the household has to answer the countrySpecific AND have the good value for it
            $andStatement = $qb->expr()->andX();
            $andStatement->add('cs'.$i . '.fieldString = :csName'.$i);
            $andStatement->add('csa'.$i . '.answer ' . $condition . ' :parameter'.$i);
            $userConditionsStatement->add($andStatement);
            $qb->addSelect('cs'.$i . '.fieldString');
        }

        // The selection criteria is directly a field in the Household table
        elseif ($criterion['type'] === 'table_field') {
            $userConditionsStatement->add('hh.' . $field . $condition . ' :parameter'.$i);
            $qb->addSelect('(CASE WHEN hh.' . $field . $condition . ' :parameter'.$i . ' THEN hh. ' . $field . ' ELSE :null END) AS ' . $field.$i)
                ->setParameter('null', null);
        } elseif ($criterion['type'] === 'other') {
            // The selection criteria is the size of the household
            if ($field === 'householdSize') {
                $userConditionsStatement->add('SIZE(hh.beneficiaries) ' . $condition . ' :parameter'.$i);
                $qb->addSelect('(CASE WHEN SIZE(hh.beneficiaries) ' . $condition . ' :parameter'.$i .' THEN SIZE(hh.beneficiaries) ELSE :null END) AS ' . $field.$i)
                    ->setParameter('null', null);
            }
            // The selection criteria is the location type (residence, camp...)
            elseif ($field === 'locationType') {
                $qb->leftJoin('hh.householdLocations', 'hl'.$i, Join::WITH, 'hl'.$i . '.type ' . $condition . ' :parameter'.$i);
                $userConditionsStatement->add('hl'.$i . '.type ' . $condition . ' :parameter'.$i);
                $qb->addSelect('hl'.$i . '.type AS ' . $field.$i);
            }
            // The selection criteria is the name of the camp in which the household lives
            elseif ($field === 'campName') {
                $qb->leftJoin('hh.householdLocations', 'hl'.$i, Join::WITH, 'hl'.$i . '.type = :camp')
                    ->leftJoin('hl' . $i . '.campAddress', 'ca'.$i)
                    ->leftJoin('ca'.$i.'.camp', 'c'.$i, Join::WITH, 'c'.$i . '.id = :parameter'.$i)
                    ->setParameter('camp', 'camp');
                $userConditionsStatement->add('c'.$i . '.id = :parameter'.$i);
                $qb->addSelect('c'.$i . '.id AS ' . $field.$i);
            } elseif ($field === 'currentAdm1' || $field === 'currentAdm2' || $field === 'currentAdm3' || $field === 'currentAdm4') {
                $qb->leftJoin('hh.householdLocations', 'hl'.$i)
                    ->leftJoin('hl'.$i.'.campAddress', 'ca'.$i)
                    ->leftJoin('ca'.$i.'.camp', 'c'.$i)
                    ->leftJoin('hl'.$i.'.address', 'ad'.$i)
                    ->leftJoin(
                        Location::class,
                        'l'.$i,
                        Join::WITH,
                        'l'.$i.'.id = COALESCE(IDENTITY(c'.$i.".location, 'id'), IDENTITY(ad".$i.".location, 'id'))"
                    );
                $andStatement = $qb->expr()->andX();
                $andStatement->add('hl'.$i.'.locationGroup = :current');
                $qb->setParameter('current', 'current');

                if ('currentAdm1' === $field) {
                    $qb->leftJoin('l'.$i.'.adm4', 'adm4'.$i)
                        ->leftJoin('l'.$i.'.adm3', 'locAdm3'.$i)
                        ->leftJoin('l'.$i.'.adm2', 'locAdm2'.$i)
                        ->leftJoin('l'.$i.'.adm1', 'locAdm1'.$i)
                        ->leftJoin(Adm3::class, 'adm3'.$i, Join::WITH, 'adm3'.$i.'.id = COALESCE(IDENTITY(adm4'.$i.".adm3, 'id'), locAdm3".$i.'.id)')
                        ->leftJoin(Adm2::class, 'adm2'.$i, Join::WITH, 'adm2'.$i.'.id = COALESCE(IDENTITY(adm3'.$i.".adm2, 'id'), locAdm2".$i.'.id)')
                        ->leftJoin(
                            Adm1::class,
                            'adm1'.$i,
                            Join::WITH,
                            'adm1'.$i.'.id = COALESCE(IDENTITY(adm2'.$i.".adm1, 'id'), locAdm1".$i.'.id) AND adm1'.$i.'.id '.$condition.' :parameter'.$i
                        );
                    $andStatement->add('adm1'.$i.'.id '.$condition.' :parameter'.$i);
                    $qb->addSelect('adm1'.$i.'.id AS '.$field.$i);
                } elseif ('currentAdm2' === $field) {
                    $qb->leftJoin('l'.$i.'.adm4', 'adm4'.$i)
                        ->leftJoin('l'.$i.'.adm3', 'locAdm3'.$i)
                        ->leftJoin('l'.$i.'.adm2', 'locAdm2'.$i)
                        ->leftJoin(Adm3::class, 'adm3'.$i, Join::WITH, 'adm3'.$i.'.id = COALESCE(IDENTITY(adm4'.$i.".adm3, 'id'), locAdm3".$i.'.id)')
                        ->leftJoin(
                            Adm2::class,
                            'adm2'.$i,
                            Join::WITH,
                            'adm2'.$i.'.id = COALESCE(IDENTITY(adm3'.$i.".adm2, 'id'), locAdm2".$i.'.id) AND adm2'.$i.'.id '.$condition.' :parameter'.$i
                        );
                    $andStatement->add('adm2'.$i.'.id '.$condition.' :parameter'.$i);
                    $qb->addSelect('adm2'.$i.'.id AS '.$field.$i);
                } elseif ('currentAdm3' === $field) {
                    $qb->leftJoin('l'.$i.'.adm4', 'adm4'.$i)
                        ->leftJoin('l'.$i.'.adm3', 'locAdm3'.$i)
                        ->leftJoin(
                            Adm3::class,
                            'adm3'.$i,
                            Join::WITH,
                            'adm3'.$i.'.id = COALESCE(IDENTITY(adm4'.$i.".adm3, 'id'), locAdm3".$i.'.id) AND adm3'.$i.'.id '.$condition.' :parameter'.$i
                        );
                    $andStatement->add('adm3'.$i.'.id '.$condition.' :parameter'.$i);
                    $qb->addSelect('adm3'.$i.'.id AS '.$field.$i);
                } elseif ('currentAdm4' === $field) {
                    $qb->leftJoin('l'.$i.'.adm4', 'adm4'.$i, Join::WITH, 'adm4'.$i.'.id '.$condition.' :parameter'.$i);
                    $andStatement->add('adm4'.$i.'.id '.$condition.' :parameter'.$i);
                    $qb->addSelect('adm4'.$i.'.id AS '.$field.$i);
                }
                $userConditionsStatement->add($andStatement);
            }
        }
    }

    private function getBeneficiaryWithCriterion(&$qb, $field, $condition, $criterion, int $i, &$userConditionsStatement)
    {
        // The selection criteria is a vulnerability criterion
        if ($criterion['table_string'] === 'vulnerabilityCriteria') {
            $this->hasVulnerabilityCriterion($qb, 'b', $condition, $field, $userConditionsStatement, $i);
        }
        // The selection criteria is directly a field in the Beneficiary table
        else if ($criterion['type'] === 'table_field') {
            if (in_array($field, ['dateOfBirth', 'gender'])) {
                if (!in_array('prsn', $qb->getAllAliases())) {
                    $qb->join('b.person', 'prsn');
                }

                $userConditionsStatement->add('prsn.'.$field.$condition.' :parameter'.$i);
                $qb->addSelect('(CASE WHEN prsn.'.$field.$condition.' :parameter'.$i.' THEN prsn.'.$field.' ELSE :null END) AS '.$field.$i)
                    ->setParameter('null', null);
            } else {
                $userConditionsStatement->add('b.'.$field.$condition.' :parameter'.$i);
                $qb->addSelect('(CASE WHEN b.'.$field.$condition.' :parameter'.$i.' THEN b.'.$field.' ELSE :null END) AS '.$field.$i)
                    ->setParameter('null', null);
            }
        } elseif ('other' === $criterion['type']) {
            // The selection criteria is the last distribution
            if ('hasNotBeenInDistributionsSince' === $field) {
                $qb->leftJoin('b.assistanceBeneficiary', 'db'.$i)
                    ->leftJoin('db'.$i.'.assistance', 'd'.$i)
                    // If has criteria, add it to the select to calculate weight later
                    ->addSelect('(CASE WHEN d'.$i.'.dateDistribution < :parameter'.$i.' THEN d'.$i.'.dateDistribution WHEN SIZE(b.assistanceBeneficiary) = 0 THEN :noDistribution ELSE :null END)'.' AS '.$criterion['field_string'].$i)
                    ->setParameter('noDistribution', 'noDistribution')
                    ->setParameter('null', null);
                // The beneficiary answers the criteria if they didn't have a distribution after this date or if they never had a distribution at all
                $userConditionsStatement->add($qb->expr()->eq('SIZE(b.assistanceBeneficiary)', '0'));
                $userConditionsStatement->add($qb->expr()->lte('d'.$i.'.dateDistribution', ':parameter'.$i));
            }
        }
    }

    private function hasVulnerabilityCriterion(&$qb, $on, $conditionString, $vulnerabilityName, &$userConditionsStatement, int $i)
    {
        // Find a way to act directly on the join table beneficiary_vulnerability
        if ('true' == $conditionString) {
            $qb->leftJoin("$on.vulnerabilityCriteria", "vc$i", Join::WITH, "vc$i.fieldString = :vulnerability$i");
            $userConditionsStatement->add($qb->expr()->eq("vc$i.fieldString", ":vulnerability$i"));
            // If has criteria, add it to the select to calculate weight later
            $qb->addSelect("vc$i.fieldString AS $on$vulnerabilityName$i");
        } else {
            $qb->leftJoin("$on.vulnerabilityCriteria", "vc$i", Join::WITH, "vc$i.fieldString <> :vulnerability$i");
            $userConditionsStatement->add($qb->expr()->eq("SIZE($on.vulnerabilityCriteria)", 0))
                ->add($qb->expr()->neq("vc$i.fieldString", ":vulnerability$i"));
            // The beneficiary doesn"t have a vulnerability A if all their vulnerabilities are != A or if they have no vulnerabilities
            // If has criteria, add it to the select to calculate weight later
            $qb->addSelect("(CASE WHEN vc$i.fieldString <> :vulnerability$i THEN vc$i.fieldString WHEN SIZE($on.vulnerabilityCriteria) = 0 THEN :noCriteria ELSE :null END) AS $on$vulnerabilityName$i")
                ->setParameter('noCriteria', 'noCriteria')
                ->setParameter('null', null);
        }
        $qb->setParameter(':vulnerability'.$i, $vulnerabilityName);
    }

    private function hasValidSmartcardCriterion(QueryBuilder &$qb, $on, $value, int $i)
    {
        $subQueryForSC = $this->_em->createQueryBuilder()
            ->select("sc$i.id")
            ->from(Smartcard::class, "sc$i")
            ->andWhere("IDENTITY(sc$i.beneficiary) = $on.id")
            ->andWhere("sc$i.state IN ('".SmartcardStates::ACTIVE."')")
            ->getDQL()
        ;

        if ($value == true) {
            $qb->andWhere("EXISTS($subQueryForSC)");
        } else {
            $qb->andWhere("NOT EXISTS($subQueryForSC)");
        }
    }

    private function getHeadWithCriterion(&$qb, $field, $condition, $criterion, int $i, &$userConditionsStatement)
    {
        $qb->leftJoin('hh.beneficiaries', 'hhh'.$i)
            ->andWhere('hhh'.$i.'.status = 1');
        $qb->join('hhh'.$i.'.person', 'prsn'.$i);

        // The selection criteria is directly a field in the Beneficiary table
        if ('table_field' === $criterion['type']) {
            // The criterion name identifies the criterion (eg. headOfHouseholdDateOfBirth) whereas the field is gonna identify the table field (eg. dateOfBirth) in the Beneficiary table
            $criterionName = $field;
            if ('headOfHouseholdDateOfBirth' === $field) {
                $userConditionsStatement->add('prsn'.$i.'.dateOfBirth '.$condition.' :parameter'.$i);
                $qb->addSelect('(CASE WHEN prsn'.$i.'.dateOfBirth '.$condition.' :parameter'.$i.' THEN prsn'.$i.'.dateOfBirth ELSE :null END) AS '.$criterionName.$i)
                    ->setParameter('null', null);
            } elseif ('headOfHouseholdGender' === $field) {
                $userConditionsStatement->add('prsn'.$i.'.gender '.$condition.' :parameter'.$i);
                $qb->addSelect('(CASE WHEN prsn'.$i.'.gender '.$condition.' :parameter'.$i.' THEN prsn'.$i.'.gender ELSE :null END) AS '.$criterionName.$i)
                    ->setParameter('null', null);
            } else {
                $userConditionsStatement->add('hhh'.$i.'.'.$field.$condition.' :parameter'.$i);
                $qb->addSelect('(CASE WHEN hhh'.$i.'.'.$field.$condition.' :parameter'.$i.' THEN hhh'.$i.'.'.$field.' ELSE :null END) AS '.$criterionName.$i)
                    ->setParameter('null', null);
            }
        } elseif ('other' === $criterion['type']) {
            if ('disabledHeadOfHousehold' === $field) {
                $this->hasVulnerabilityCriterion($qb, 'hhh'.$i, $condition, 'disabled', $userConditionsStatement, $i);
            }
            if ('hasValidSmartcard' === $field) {
                $this->hasValidSmartcardCriterion($qb, 'hhh'.$i, $criterion['value'], $i);
            }
        }
    }

    /**
     * @param Household $household
     *
     * @return int
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countByHousehold(Household $household): int
    {
        return $this->createQueryBuilder('b')
            ->select('COUNT(DISTINCT b)')
            ->where('b.household = :household')
            ->setParameter('household', $household)
            ->getQuery()->getSingleScalarResult();
    }

    /**
     * @param BeneficiaryFilterInputType $filterInputType
     *
     * @return Paginator
     */
    public function findByParams(BeneficiaryFilterInputType $filterInputType): Paginator
    {
        $qbr = $this->createQueryBuilder('b')
            ->andWhere('b.archived = 0');

        if ($filterInputType->hasIds()) {
            $qbr->andWhere('b.id IN (:ids)')
                ->setParameter('ids', $filterInputType->getIds());
        }

        return new Paginator($qbr);
    }

    /**
     * @param Assistance                      $assistance
     * @param BeneficiaryFilterInputType|null $filter
     * @param BeneficiaryOrderInputType|null  $orderBy
     * @param Pagination|null                 $pagination
     * @param bool|null                       $onlyLive
     *
     * @return Paginator|Assistance[]
     */
    public function findByAssistance(
        Assistance $assistance,
        ?BeneficiaryFilterInputType $filter,
        ?BeneficiaryOrderInputType $orderBy = null,
        ?Pagination $pagination = null,
        ?bool $onlyLive = null
    ): Paginator
    {
        $qbr = $this->createQueryBuilder('b')
            ->join('b.assistanceBeneficiary', 'ab')
            ->leftJoin('b.person', 'p')
            ->andWhere('ab.assistance = :assistance')
            ->setParameter('assistance', $assistance);

        if($onlyLive){
            $qbr->andWhere('b.archived = :archived')
                ->andWhere('ab.removed = :removed')
                ->setParameter('archived', 0)
                ->setParameter('removed', 0);
        }

        if ($pagination) {
            $qbr->setMaxResults($pagination->getLimit());
            $qbr->setFirstResult($pagination->getOffset());
        }

        if ($filter) {
            if ($filter->hasFulltext()) {
                $qbr->andWhere('(p.localGivenName LIKE :fulltext OR 
                                p.localFamilyName LIKE :fulltext OR
                                p.localParentsName LIKE :fulltext OR
                                p.enGivenName LIKE :fulltext OR
                                p.enFamilyName LIKE :fulltext OR
                                p.enParentsName LIKE :fulltext OR
                                p.enParentsName LIKE :fulltext)')
                    ->setParameter('fulltext', '%'.$filter->getFulltext().'%');
            }
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                switch ($name) {
                    case BeneficiaryOrderInputType::SORT_BY_ID:
                        $qbr->orderBy('b.id', $direction);
                        break;
                    case BeneficiaryOrderInputType::SORT_BY_LOCAL_FAMILY_NAME:
                        $qbr->orderBy('p.localFamilyName', $direction);
                        break;
                    case BeneficiaryOrderInputType::SORT_BY_LOCAL_GIVEN_NAME:
                        $qbr->orderBy('p.localGivenName', $direction);
                        break;
                    case BeneficiaryOrderInputType::SORT_BY_NATIONAL_ID:
                        $qbr->leftJoin('p.nationalIds', 'n', 'WITH', 'n.idType = :type')
                            ->setParameter('type', NationalIdType::NATIONAL_ID)
                            ->orderBy('n.idNumber', $direction);
                        break;
                    default:
                        throw new \InvalidArgumentException('Invalid order by directive '.$name);
                }
            }
        }

        return new Paginator($qbr);
    }
}
