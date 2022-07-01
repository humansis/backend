<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring;

use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRuleOptionsEnum;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRule;

/**
 * All methods needs to be public function(Household $household, ScoringRule $rule): int
 *
 * @package NewApiBundle\Component\Assistance\Scoring
 */
final class RulesCalculation
{
    /**
     * @param Household $household
     * @param ScoringRule $rule
     *
     * @return int
     */
    public function dependencyRatio(Household $household, ScoringRule $rule): int
    {
        if ($household->getBeneficiaries()->count() === 2 ) {
            return $rule->getOptionByValue('1 (mid.)')->getScore();
        } else if ($household->getBeneficiaries()->count() > 2) {
            return $rule->getOptionByValue('>1 (hight dep.)')->getScore();
        }

        return 0;
    }

    /**
     * @param Household $household
     * @param ScoringRule $rule
     *
     * @return int
     */
    public function singleParentHeaded(Household $household, ScoringRule $rule): int
    {
        /** @var VulnerabilityCriterion $headVulnerability */
        foreach ($household->getHouseholdHead()->getVulnerabilityCriteria() as $headVulnerability) {
            if ($headVulnerability->getFieldString() === VulnerabilityCriterion::CRITERION_SOLO_PARENT) {
                return $rule->getOptionByValue(ScoringRuleOptionsEnum::VULNERABILITY_SOLO_PARENT)->getScore();
            }
        }

        return 0;
    }

    /**
     * @param Household $household
     * @param ScoringRule $rule
     *
     * @return int
     */
    public function pregnantOrLactating(Household $household, ScoringRule $rule): int
    {
        $totalScore = 0;

        foreach ($household->getBeneficiaries() as $beneficiary) {
            /** @var VulnerabilityCriterion $headVulnerability */
            foreach ($beneficiary->getVulnerabilityCriteria() as $headVulnerability) {
                if ($headVulnerability->getFieldString() === VulnerabilityCriterion::CRITERION_PREGNANT || $headVulnerability->getFieldString() === VulnerabilityCriterion::CRITERION_LACTATING) {
                    $totalScore += $rule->getOptionByValue(ScoringRuleOptionsEnum::VULNERABILITY_PREGNANT_OR_LACTATING)->getScore();
                    continue 2;
                }
            }
        }

        return $totalScore;
    }

    /**
     * @param Household $household
     * @param ScoringRule $rule
     *
     * @return int
     */
    public function noOfChronicallyIll(Household $household, ScoringRule $rule): int
    {
        /** @var CountrySpecificAnswer $countrySpecificAnswer */
        foreach ($household->getCountrySpecificAnswers() as $countrySpecificAnswer) {
            if ($countrySpecificAnswer->getCountrySpecific()->getFieldString() === 'No of chronically ill') {
                if ( (int) $countrySpecificAnswer->getAnswer() === 1) {
                    return $rule->getOptionByValue(ScoringRuleOptionsEnum::CHRONICALLY_ILL_ONE)->getScore();
                } else if ( (int) $countrySpecificAnswer->getAnswer() > 1) {
                    return $rule->getOptionByValue(ScoringRuleOptionsEnum::CHRONICALLY_ILL_TWO_OR_MORE)->getScore();
                }
            }
        }

        return 0;
    }
}
