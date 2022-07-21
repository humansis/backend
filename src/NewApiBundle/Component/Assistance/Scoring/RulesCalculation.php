<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring;

use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRuleOptionsEnum;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRule;
use NewApiBundle\Utils\Floats;
use NewApiBundle\Enum\PersonGender;

/**
 * All methods needs to be public function(Household $household, ScoringRule $rule): int
 *
 * Every public method in this class needs to be included in ScoringRuleOptionsEnum. Also every value in
 * ScoringRuleOptionsEnum has to have implementation in this class.
 */
final class RulesCalculation
{
    /**
     * @param Household $household
     * @param ScoringRule $rule
     *
     * @return int
     */
    public function dependencyRatioUkr(Household $household, ScoringRule $rule): int
    {
        $childAgeLimit = 17;
        $workingAgeLimit = 50;

        $children = 0;
        $elders = 0;
        $adultsInWorkingAge = 0;

        foreach ($household->getBeneficiaries() as $member) {
            if (is_null($member->getAge())) {
                continue;
            }

            if ($member->getAge() <= $childAgeLimit) {
                $children++;
            } elseif ($member->getAge() >= $workingAgeLimit) {
                $elders++;
            } else {
                $adultsInWorkingAge++;
            }
        }

        if ($adultsInWorkingAge === 0) {
            return $rule->getOptionByValue(ScoringRuleOptionsEnum::DEPENDENCY_RATIO_HIGH)->getScore();
        }

        $dependencyRatio = ($children + $elders) / $adultsInWorkingAge;

        if (Floats::compare($dependencyRatio, 1.0)) {
            return $rule->getOptionByValue(ScoringRuleOptionsEnum::DEPENDENCY_RATIO_MID)->getScore();
        } else if ($dependencyRatio > 1.0) {
            return $rule->getOptionByValue(ScoringRuleOptionsEnum::DEPENDENCY_RATIO_HIGH)->getScore();
        }

        return 0;
    }

    public function complexDependencyRatio(Household $household, ScoringRule $rule): int
    {
        $beneficiaries = $household->getBeneficiaries();
        $children = 0;
        $adultsOver60 = 0;
        $adultsDisabled = 0;
        $adultsChronicallyIll = 0;
        $adultsWorking = 0;

        foreach ($beneficiaries as $beneficiary) {
            $age = $beneficiary->getAge();
            $isChronicallyIll = $beneficiary->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_CHRONICALLY_ILL);
            $isDisabled = $beneficiary->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_DISABLED);

            switch (true) {
                case $age < 18:
                    $children++;
                    break;

                case $age < 60 && $isChronicallyIll:
                    $adultsWorking++;
                    $adultsChronicallyIll++;
                    break;

                case $age < 60 && $isDisabled:
                    $adultsWorking++;
                    $adultsDisabled++;
                    break;

                case $age < 60:
                    $adultsWorking++;
                    break;

                default:
                    $adultsOver60++;
            }
        }

        $ratio = ((float)($children + $adultsOver60 + $adultsDisabled + $adultsChronicallyIll)) / $adultsWorking;

        switch (true) {
            case $ratio > 0 && $ratio <= 1:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::VERY_LOW_VULNERABILITY)->getScore();
                break;

            case (int)$ratio === 2:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::LOW_VULNERABILITY)->getScore();
                break;

            case (int)$ratio === 3:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::MODERATE_VULNERABILITY)->getScore();
                break;

            case (int)$ratio === 4:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::HIGH_VULNERABILITY)->getScore();
                break;

            case (int)$ratio === 5:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::VERY_HIGH_VULNERABILITY)->getScore();
                break;

            case (int)$ratio === 0 || $ratio >= 6:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::EXTREME_VULNERABILITY)->getScore();
                break;

            default:
                $result = 0;
        }

        return $result;
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
                if ((int)$countrySpecificAnswer->getAnswer() === 1) {
                    return $rule->getOptionByValue(ScoringRuleOptionsEnum::CHRONICALLY_ILL_ONE)->getScore();
                } else if ((int)$countrySpecificAnswer->getAnswer() > 1) {
                    return $rule->getOptionByValue(ScoringRuleOptionsEnum::CHRONICALLY_ILL_TWO_OR_MORE)->getScore();
                }
            }
        }

        return 0;
    }

    public function hhHeadVulnerability(Household $household, ScoringRule $rule): int
    {
        $hhhGender = $household->getHouseholdHead()->getPerson()->getGender();
        $hhhAge = $household->getHouseholdHead()->getPerson()->getAge();
        $hhhVulnerabilityCriteria = $household->getHouseholdHead()->getVulnerabilityCriteria();
        $hhhVulnerabilityCriteria = $hhhVulnerabilityCriteria->toArray();

        return $this->memberScoring($hhhVulnerabilityCriteria, $hhhGender, $hhhAge, $rule);
    }

    public function hhMembersVulnerability(Household $household, ScoringRule $rule): int
    {
        $beneficiaries = $household->getBeneficiaries();

        $totalScore = 0;
        foreach ($beneficiaries as $beneficiary) {
            $memberVulnerabilityCriteria = $beneficiary->getVulnerabilityCriteria();
            $memberGender = $beneficiary->getPerson()->getGender();
            $memberAge = $beneficiary->getPerson()->getAge();

            $totalScore += $this->memberScoring($memberVulnerabilityCriteria, $memberGender, $memberAge, $rule);
        }

        return $totalScore;
    }

    private function memberScoring($vulnerabilityCriteria, $gender, $age, ScoringRule $rule): int
    {
        /** @var VulnerabilityCriterion $vulnerabilityCriterion */
        $result = 0;
        foreach ($vulnerabilityCriteria as $vulnerabilityCriterion) {
            $criterion = $vulnerabilityCriterion->getFieldString();
            switch (true) {
                case $criterion === VulnerabilityCriterion::CRITERION_NO_VULNERABILITY:
                    $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::NO_VULNERABILITY)->getScore();
                    break 2;

                case $criterion === VulnerabilityCriterion::CRITERION_DISABLED:
                    $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::DISABLED)->getScore();
                    break 2;

                case $criterion === VulnerabilityCriterion::CRITERION_CHRONICALLY_ILL:
                    $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::CHRONICALLY_ILL)->getScore();
                    break 2;

                case $gender === PersonGender::FEMALE &&
                    ($criterion === VulnerabilityCriterion::CRITERION_PREGNANT ||
                     $criterion === VulnerabilityCriterion::CRITERION_LACTATING):
                    $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::PREGNANT_LACTATING_FEMALE)->getScore();
                    break 2;

                case $age < 18:
                    $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::AGE_18)->getScore();
                    break 2;

                case $age > 59:
                    $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::AGE_59)->getScore();
                    break 2;

                default:
                    break;
            }
        }

        return $result;
    }
}
