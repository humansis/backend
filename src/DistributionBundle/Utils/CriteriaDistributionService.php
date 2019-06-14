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
        $isCount = false; // TODO: delete
        $criteria = $filters['criteria'];

        $selectableBeneficiaries = $this->em->getRepository(Beneficiary::class)
                ->getDistributionBeneficiaries($criteria, $project, $countryISO3, $threshold, $distributionType);

        $beneficiaryScores = [];
        $reachedBeneficiaries = [];
        
        // 1. Calculate the selection score foreach beneficiary
        foreach ($selectableBeneficiaries as $beneficiary) {
            $score = 0;

            // 1.1 Update the score foreach selection criterion
            foreach ($criteria as $criterion) {
                $fieldString = $criterion['field_string'];
                $valueString = $criterion['value_string'];

                // If the distribution type is Household and the beneficiary is not the head, count only the criteria targetting the beneficiaries
                if ($distributionType !== '0' || $beneficiary['headId'] === $beneficiary['id'] || $criterion['target'] === 'Beneficiary') {
    
                    // In the case of vulnerabilityCriteria, dql forces us to add a b or a hhh in front of the key for it to be unique
                    if ($criterion['table_string'] === 'vulnerabilityCriteria') {
                        $valueString = $fieldString; // value = disabled/lactating etc
                        $fieldString = 'b' . $fieldString; // fieldString = bdisabled/blactating etc
                    } if ($criterion['field_string'] === 'disabledHeadOfHousehold') {
                        $valueString = 'disabled'; // value = disabled/lactating etc
                        $fieldString = 'hhhdisabled';
                    }
        
                    if (array_key_exists($fieldString, $beneficiary) && !is_null($beneficiary[$fieldString])) {

                        // Format to string to be able to use ===
                        if (gettype($beneficiary[$fieldString]) === 'integer') {
                            $beneficiary[$fieldString] = (string) $beneficiary[$fieldString];
                        } else if ($beneficiary[$fieldString] instanceof \DateTime) {
                            $beneficiary[$fieldString] = $beneficiary[$fieldString]->format('Y-m-d');
                        }
    
                        switch ($criterion['condition_string']) {
                            case '>':
                                $score = $beneficiary[$fieldString] > $valueString ? $score + $criterion['weight'] : $score;
                                break;
                            case '<':
                                $score = $beneficiary[$fieldString] < $valueString ? $score + $criterion['weight'] : $score;
                                break;
                            case '<=':
                                $score = $beneficiary[$fieldString] <= $valueString ? $score + $criterion['weight'] : $score;
                                break;
                            case '>=':
                                $score = $beneficiary[$fieldString] >= $valueString ? $score + $criterion['weight'] : $score;
                                break;
                            case '!=':
                            case 'false':
                                $score = $beneficiary[$fieldString] !== $valueString ? $score + $criterion['weight'] : $score;
                                break;
                            case '=':
                            case 'true':
                                if (!is_null($valueString)) {
                                    $score = $beneficiary[$fieldString] === $valueString ? $score + $criterion['weight'] : $score;
                                }
                                break;    
                        }
                    }
                    // If there is no distribution for a beneficiary, it means it has not been in a distribution since forever
                    else if ($criterion['field_string'] === 'hasNotBeenInDistributionsSince') {
                        $score += $criterion['weight'];
                    } else if (!array_key_exists($fieldString, $beneficiary) && $criterion['condition_string'] === '!=' || $criterion['condition_string'] === 'false') {
                        $score += $criterion['weight'];
                    }
                }
            }

            // 1.2. In case it is a distribution targetting households, gather the score to the head, else just store it
            if ($distributionType === '0') {
                if (!array_key_exists($beneficiary['headId'], $beneficiaryScores)) {
                    $beneficiaryScores[$beneficiary['headId']] = $score;
                } else {
                    $beneficiaryScores[$beneficiary['headId']] = intval($beneficiaryScores[$beneficiary['headId']]) + $score;
                }
            } else {
                $beneficiaryScores[$beneficiary['id']] = $score;
            }
        }
        
        // 2. Verify who is above the threshold 
        foreach ($beneficiaryScores as $selectableBeneficiaryId => $score) {
            if ($score >= $threshold) {
                array_push($reachedBeneficiaries, $selectableBeneficiaryId);
            }
        }
        
        dump($beneficiaryScores);

        if ($isCount) {
            return ['number' =>  count($reachedBeneficiaries)];
        } else {
            // !!!! Those are ids, not directly beneficiaries !!!!
            return ['finalArray' =>  $reachedBeneficiaries];
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
                if ($criterion['target'] == "Household") {
                    $count += $this->countHousehold($criterion, $countryISO3, $household);
                } elseif ($criterion['target'] == "Beneficiary") {
                    $beneficiaries = $this->em->getRepository(Beneficiary::class)->findByHousehold($household);
                    foreach ($beneficiaries as $beneficiary) {
                        $count += $this->countBeneficiary($criterion, $beneficiary);
                    }
                } elseif ($criterion['target'] == "Head") {
                    $headBeneficiary = $this->em->getRepository(Beneficiary::class)->getHeadOfHousehold($household);
                    $criterion = $this->formatHeadOfHouseholdCriteria($criterion);
                    $count += $this->countBeneficiary($criterion, $headBeneficiary);
                } else {
                    throw new \Exception("A problem was found. Target is unknown");
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
                    if ($criterion['target'] == "Household") {
                        $count += $this->countHousehold($criterion, $countryISO3, $household);
                    } elseif ($criterion['target'] == "Beneficiary") {
                        $count += $this->countBeneficiary($criterion, $beneficiary);
                    } elseif ($criterion['target'] == "Head") {
                        $headBeneficiary = $this->em->getRepository(Beneficiary::class)->getHeadOfHousehold($household);
                        $criterion = $this->formatHeadOfHouseholdCriteria($criterion);
                        $count += $this->countBeneficiary($criterion, $headBeneficiary);
                    } else {
                        throw new \Exception("A problem was found. Target of beneficiary is unknown");
                    }
                }

                if ($count >= $threshold) {
                    array_push($finalArray, $beneficiary);
                }
            }
        }

        return $finalArray;
    }

    public function formatHeadOfHouseholdCriteria($criterion) {
        if ($criterion['field_string'] === 'headOfHouseholdDateOfBirth') {
            $criterion['field_string'] = 'dateOfBirth';
        } else if ($criterion['field_string'] === 'headOfHouseholdGender') {
            $criterion['field_string'] = 'gender';
        }
        return $criterion;
    }

    /**
     * @param array $criterion
     * @param string $countryISO3
     * @param Household $household
     * @return int
     */
    public function countHousehold(array $criterion, string $countryISO3, Household $household)
    {
        // If it is not a countrySpecific nor a vulnarabilityCriteria
        if (key_exists('table_string', $criterion) && $criterion['table_string'] === 'Personnal') {
            $listOfCriteria = $this->configurationLoader->criteria;
            $type = $listOfCriteria[$criterion['field_string']]['type'];
            $value = $criterion['value_string'];
            // If the type is table_field, it means we can directly fetch the value in the DB
            if ($type === 'table_field') {
                $hasVC = $this->em->getRepository(Household::class)
                    ->hasParameter($criterion['field_string'], $criterion['condition_string'], $criterion['value_string'], $household->getId());
            }
            // The selection criteria is the size of the household
            else if ($type === 'size') {
                $hasVC = $this->em->getRepository(Household::class)
                    ->hasSize($criterion['value_string'], $criterion['condition_string'], $household->getId());
            }
            // The selection criteria is the location type (residence, camp...)
            else if ($type === 'householdLocationType') {
                $hasVC = $this->em->getRepository(Household::class)
                    ->hasLocationType($criterion['condition_string'], $criterion['value_string'], $household->getId());
            } 
            // The selection criteria is the name of the camp in which the household lives
            else if ($type === 'campName') {
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
            // The selection criteria is about the beneficiary's last distribution
            if ($type === 'distribution_beneficiary') {
                $hasVC = !$this->em->getRepository(Beneficiary::class)->lastDistributionAfter($criterion['value_string'], $beneficiary->getId());
            }
            // Table_field means we can directly fetch the value in the DB
            else if ($type === 'table_field') {
                $hasVC = $this->em->getRepository(Beneficiary::class)
                    ->hasParameter($criterion['field_string'], $criterion['condition_string'], $criterion['value_string'], $beneficiary->getId());
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
