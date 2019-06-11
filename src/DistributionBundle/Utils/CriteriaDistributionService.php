<?php


namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Entity\SelectionCriteria;
use Doctrine\ORM\EntityManagerInterface;
use ProjectBundle\Entity\Project;

/**
 * Class CriteriaDistributionService
 * @package DistributionBundle\Utils
 */
class CriteriaDistributionService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ConfigurationLoader $configurationLoader */
    private $configurationLoader;


    /**
     * CriteriaDistributionService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ConfigurationLoader $configurationLoader
     * @throws \Exception
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ConfigurationLoader $configurationLoader
    ) {
        $this->em = $entityManager;
        $this->configurationLoader = $configurationLoader;
    }


    /**
     * @param array $filters
     * @param Project $project
     * @param int $threshold
     * @param $isCount
     * @return mixed
     * @throws \Exception
     */
    public function load(array $filters, Project $project, int $threshold, bool $isCount)
    {
        $countryISO3 = $filters['countryIso3'];

        $distributionType = $filters['distribution_type'];

        if ($distributionType == "household" || $distributionType == "Household" || $distributionType == '0') {
            $finalArray = $this->loadHousehold($filters['criteria'], $threshold, $countryISO3, $project);
        } elseif ($distributionType == "individual" || $distributionType == "Individual" || $distributionType == '1') {
            $finalArray = $this->loadBeneficiary($filters['criteria'], $threshold, $countryISO3, $project);
        } else {
            throw new \Exception("A problem was found. Distribution type is unknown");
        }

        if ($isCount) {
            return ['number' => count($finalArray)];
        } else {
            return ['finalArray' => $finalArray];
        }
    }

    /**
     * @param array $criteria
     * @param int $threshold
     * @param string $countryISO3
     * @param Project $project
     * @return array
     * @throws \Exception
     */
    public function loadHousehold(array $criteria, int $threshold, string $countryISO3, Project $project)
    {
        $households = $this->em->getRepository(Household::class)->getUnarchivedByProject($project);
        $finalArray = array();

        foreach ($households as $household) {
            $count = 0;

            foreach ($criteria as $criterion) {
                if ($criterion['kind_beneficiary'] == "Household") {
                    $count += $this->countHousehold($criterion, $countryISO3, $household);
                } elseif ($criterion['kind_beneficiary'] == "Beneficiary") {
                    $beneficiaries = $this->em->getRepository(Beneficiary::class)->findByHousehold($household);
                    foreach ($beneficiaries as $beneficiary) {
                        $count += $this->countBeneficiary($criterion, $beneficiary);
                    }
                } elseif ($criterion['kind_beneficiary'] == "Head") {
                    $headBeneficiary = $this->em->getRepository(Beneficiary::class)->getHeadOfHousehold($household);
                    $count += $this->countBeneficiary($criterion, $headBeneficiary);
                } else {
                    throw new \Exception("A problem was found. Kind of beneficiary is unknown");
                }
            }

            if ($count >= $threshold) {
                array_push($finalArray, $household);
            }
        }

        return $finalArray;
    }

    /**
     * @param array $criteria
     * @param int $threshold
     * @param string $countryISO3
     * @param Project $project
     * @return array
     * @throws \Exception
     */
    public function loadBeneficiary(array $criteria, int $threshold, string $countryISO3, Project $project)
    {
        $households = $this->em->getRepository(Household::class)->getUnarchivedByProject($project);
        $finalArray = array();

        foreach ($households as $household) {
            $beneficiaries = $this->em->getRepository(Beneficiary::class)->findByHousehold($household);

            foreach ($beneficiaries as $beneficiary) {
                $count = 0;

                foreach ($criteria as $criterion) {
                    if ($criterion['kind_beneficiary'] == "Household") {
                        $count += $this->countHousehold($criterion, $countryISO3, $household);
                    } elseif ($criterion['kind_beneficiary'] == "Beneficiary") {
                        $count += $this->countBeneficiary($criterion, $beneficiary);
                    } elseif ($criterion['kind_beneficiary'] == "Head") {
                        $headBeneficiary = $this->em->getRepository(Beneficiary::class)->getHeadOfHousehold($household);
                        $count = $this->countBeneficiary($criterion, $headBeneficiary);
                    } else {
                        throw new \Exception("A problem was found. Kind of beneficiary is unknown");
                    }
                }

                if ($count >= $threshold) {
                    array_push($finalArray, $beneficiary);
                }
            }
        }

        return $finalArray;
    }

    /**
     * @param array $criterion
     * @param string $countryISO3
     * @param Household $household
     * @return int
     */
    public function countHousehold(array $criterion, string $countryISO3, Household $household)
    {
        if (key_exists('table_string', $criterion) && $criterion['table_string'] === 'Personnal') {
            $listOfCriteria = $this->configurationLoader->criteria;
            $type = $listOfCriteria[$criterion['field_string']]['type'];
            $value = $criterion['value_string'];
            if ($type === 'table_field') {
                $hasVC = $this->em->getRepository(Household::class)
                    ->hasParameter($criterion['field_string'], $criterion['condition_string'], $criterion['value_string'], $household->getId());
            } else if ($type === 'size') {
                $numberDependents = $household->getNumberDependents();
                switch ($criterion['condition_string'])
                {
                    case '=':
                        $hasVC = $numberDependents === $value;
                    case '!=':
                        $hasVC = $numberDependents !== $value;
                    case '<=':
                        $hasVC = $numberDependents <= $value;
                    case '>=':
                        $hasVC = $numberDependents >= $value;
                    case '<':
                        $hasVC = $numberDependents < $value;
                    case '>':
                        $hasVC = $numberDependents > $value;
                    default:
                        $hasVC = false;
                }
            } else if ($type === 'householdLocationType') {
                $hasVC = $this->em->getRepository(Household::class)
                    ->hasLocationType($criterion['condition_string'], $criterion['value_string'], $household->getId());
            } else if ($type === 'campName') {
                $hasVC = $this->em->getRepository(Household::class)
                    ->hasCamp($criterion['value_string'], $household->getId());
            }
        } else {
            $countrySpecific = $this->em->getRepository(CountrySpecific::class)->findBy(['fieldString' => $criterion['field_string'], 'countryIso3' => $countryISO3]);
            $hasVC = $this->em->getRepository(CountrySpecificAnswer::class)->hasValue($countrySpecific[0]->getId(), $criterion['value_string'], $criterion['condition_string'], $household);
        }
        return $hasVC ? $criterion['weight'] : 0;
    }

    /**
     * @param array $criterion
     * @param $beneficiary
     * @return int
     */
    public function countBeneficiary(array $criterion, Beneficiary $beneficiary)
    {
        $vulnerabilityCriteria = $this->em->getRepository(VulnerabilityCriterion::class)->findBy(['fieldString' => $criterion['field_string']]);
        $listOfCriteria = $this->configurationLoader->criteria;

        // If it is not a vulnerabilityCriteria nor a countrySpecific
        if (key_exists('table_string', $criterion) && $criterion['table_string'] === 'Personnal') {
            $type = $listOfCriteria[$criterion['field_string']]['type'];
            if ($type === 'distribution_beneficiary') {
                $hasVC = !$this->em->getRepository(Beneficiary::class)->lastDistributionAfter($criterion['value_string'], $beneficiary->getId());
            } else if ($type === 'table_field') {
                $hasVC = $this->em->getRepository(Beneficiary::class)
                    ->hasParameter($criterion['field_string'], $criterion['condition_string'], $criterion['value_string'], $beneficiary->getId());
            } else if ($type == 'gender') {
                $criterion['value_string'] = intval($criterion['value_string']);
                $hasVC = $this->em->getRepository(Beneficiary::class)->hasGender($criterion['condition_string'], $criterion['value_string'], $beneficiary->getId());
            } else if ($type === 'dateOfBirth') {
                $hasVC = $this->em->getRepository(Beneficiary::class)->hasDateOfBirth($criterion['value_string'], $criterion['condition_string'], $beneficiary->getId());
            }
            
            // It cannot be treated as below, because otherwise all the headOfHouseholds vulnerabilities would be criteria
            else if ($type === 'disabled') {
                $hasVC = $this->em->getRepository(Beneficiary::class)->hasVulnerabilityCriterion(1, $criterion['condition_string'], $beneficiary->getId());
            }
        } else {
            $hasVC = $this->em->getRepository(Beneficiary::class)->hasVulnerabilityCriterion($vulnerabilityCriteria[0]->getId(), $criterion['condition_string'], $beneficiary->getId());
        }
        return $hasVC ? $criterion['weight'] : 0;
    }

    /**
     * @param DistributionData $distributionData
     * @param SelectionCriteria $selectionCriteria
     * @param bool $flush
     * @return SelectionCriteria
     */
    public function save(DistributionData $distributionData, SelectionCriteria $selectionCriteria, bool $flush)
    {
        $selectionCriteria->setDistributionData($distributionData);
        $this->em->persist($selectionCriteria);
        if ($flush) {
            $this->em->flush();
        }
        return $selectionCriteria;
    }

    /**
     * @param string $countryISO3
     * @return array
     */
    public function getAll(string $countryISO3)
    {
        $criteria = $this->configurationLoader->load($countryISO3);
        return $criteria;
    }
}
