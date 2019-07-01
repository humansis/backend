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
use BeneficiaryBundle\Entity\Camp;

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
        $criteria = $filters['criteria'];

        foreach ($criteria as $index => $criterion) {
            if ($criterion['table_string'] === 'Personnal') {
                $criterion['type'] = $this->configurationLoader->criteria[$criterion['field_string']]['type'];
                $criteria[$index] = $criterion;
            }
        }

        $selectableBeneficiaries = $this->em->getRepository(Beneficiary::class)
                ->getDistributionBeneficiaries($criteria, $project, $countryISO3, $threshold, $distributionType);

        $beneficiaryScores = [];
        $reachedBeneficiaries = [];

        $vulnerabilityCriteria = [];
        $alreadyPassedOnce = [];

        // 1. Calculate the selection score foreach beneficiary
        foreach ($selectableBeneficiaries as $beneficiary) {
            $score = 0;

            // 1.1 Update the score foreach selection criterion
            foreach ($criteria as $index => $criterion) {
                $fieldString = $criterion['field_string'];

                // If the distribution type is Household and the beneficiary is not the head, count only the criteria targetting the beneficiaries
                if ($distributionType !== '0' || $beneficiary['headId'] === $beneficiary['id'] || $criterion['target'] === 'Beneficiary') {
    
                    // In the case of vulnerabilityCriteria, dql forces us to add a b or a hhh in front of the key for it to be unique
                    if ($criterion['table_string'] === 'vulnerabilityCriteria') {
                        $fieldString = 'b' . $fieldString; // fieldString = bdisabled/blactating etc
                    } if ($criterion['field_string'] === 'disabledHeadOfHousehold') {
                        $fieldString = 'hhh'.$index.'disabled';
                    }
                    
                    if (array_key_exists($fieldString.$index, $beneficiary) && !is_null($beneficiary[$fieldString.$index])) {
                        // Sometimes the vulnerability criteria are counted several times
                        if ($criterion['table_string'] === 'vulnerabilityCriteria') {
                            if (array_key_exists($beneficiary['id'], $vulnerabilityCriteria)) {
                                if (!in_array($fieldString, $vulnerabilityCriteria[$beneficiary['id']])) {
                                    array_push($vulnerabilityCriteria[$beneficiary['id']], $fieldString);
                                    $score += $criterion['weight'];
                                }
                            } else {
                                $vulnerabilityCriteria[$beneficiary['id']] = [$fieldString];
                                $score += $criterion['weight'];
                            }
                        }
                        // If it exists, it means we are in one of the duplicates from the vulnerability criteria bug
                        else if (!in_array($beneficiary['id'], $alreadyPassedOnce)) {
                            $score += $criterion['weight'];
                        }
                    }
                }
            }

            array_push($alreadyPassedOnce, $beneficiary['id']);

            // 1.2. In case it is a distribution targetting households, gather the score to the head, else just store it
            if ($distributionType === '0') {
                if (!array_key_exists($beneficiary['headId'], $beneficiaryScores)) {
                    $beneficiaryScores[$beneficiary['headId']] = $score;
                } else {
                    $beneficiaryScores[$beneficiary['headId']] = intval($beneficiaryScores[$beneficiary['headId']]) + $score;
                }
            } else {
                if (!array_key_exists($beneficiary['id'], $beneficiaryScores)) {
                    $beneficiaryScores[$beneficiary['id']] = $score;
                } else {
                    $beneficiaryScores[$beneficiary['id']] = intval($beneficiaryScores[$beneficiary['id']]) + $score;
                }
            }
        }
        
        // 2. Verify who is above the threshold 
        foreach ($beneficiaryScores as $selectableBeneficiaryId => $score) {
            if ($score >= $threshold) {
                array_push($reachedBeneficiaries, $selectableBeneficiaryId);
            }
        }
        

        if ($isCount) {
            return ['number' =>  count($reachedBeneficiaries)];
        } else {
            // !!!! Those are ids, not directly beneficiaries !!!!
            return ['finalArray' =>  $reachedBeneficiaries];
        }
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

    /**
     * @param string $countryISO3
     * @return array
     */
    public function getCamps(string $countryISO3)
    {
        $camps = $this->em->getRepository(Camp::class)->findByCountry($countryISO3);
        return $camps;
    }

}
