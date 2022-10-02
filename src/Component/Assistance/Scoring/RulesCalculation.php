<?php

declare(strict_types=1);

namespace Component\Assistance\Scoring;

use Entity\CountrySpecificAnswer;
use Entity\Household;
use Entity\VulnerabilityCriterion;
use Component\Assistance\Scoring\Enum\ScoringRuleCalculationOptionsEnum;
use Component\Assistance\Scoring\Model\ScoringRule;
use Enum\PersonGender;
use Utils\Floats;

/**
 * All methods need to be public function(Household $household, ScoringRule $rule): float
 *
 * Every public method in this class needs to be included in ScoringRuleOptionsEnum. Also, every value in
 * ScoringRuleOptionsEnum has to have implementation in this class.
 */
final class RulesCalculation
{
    public function dependencyRatioUkr(Household $household, ScoringRule $rule): float
    {
        $childAgeLimit = 17;
        $workingAgeLimit = 60;

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
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_HIGH)->getScore();
        }

        $dependencyRatio = ($children + $elders) / $adultsInWorkingAge;

        if (Floats::compare($dependencyRatio, 1.0)) {
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_MID)->getScore();
        }

        if ($dependencyRatio > 1.0) {
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_HIGH)->getScore();
        }

        return 0;
    }

    public function singleParentHeaded(Household $household, ScoringRule $rule): float
    {
        /** @var VulnerabilityCriterion $headVulnerability */
        foreach ($household->getHouseholdHead()->getVulnerabilityCriteria() as $headVulnerability) {
            if ($headVulnerability->getFieldString() === VulnerabilityCriterion::CRITERION_SOLO_PARENT) {
                return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::VULNERABILITY_SOLO_PARENT)->getScore(
                );
            }
        }

        return 0;
    }

    public function pregnantOrLactating(Household $household, ScoringRule $rule): float
    {
        $totalScore = 0;

        foreach ($household->getBeneficiaries() as $beneficiary) {
            /** @var VulnerabilityCriterion $headVulnerability */
            foreach ($beneficiary->getVulnerabilityCriteria() as $headVulnerability) {
                if (
                    $headVulnerability->getFieldString(
                    ) === VulnerabilityCriterion::CRITERION_PREGNANT || $headVulnerability->getFieldString(
                    ) === VulnerabilityCriterion::CRITERION_LACTATING
                ) {
                    $totalScore += $rule->getOptionByValue(
                        ScoringRuleCalculationOptionsEnum::VULNERABILITY_PREGNANT_OR_LACTATING
                    )->getScore();
                    continue 2;
                }
            }
        }

        return $totalScore;
    }

    public function noOfChronicallyIll(Household $household, ScoringRule $rule): float
    {
        /** @var CountrySpecificAnswer $countrySpecificAnswer */
        foreach ($household->getCountrySpecificAnswers() as $countrySpecificAnswer) {
            if ($countrySpecificAnswer->getCountrySpecific()->getFieldString() === 'No of chronically ill') {
                if ((int)$countrySpecificAnswer->getAnswer() === 1) {
                    return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::CHRONICALLY_ILL_ONE)->getScore();
                } else {
                    if ((int)$countrySpecificAnswer->getAnswer() > 1) {
                        return $rule->getOptionByValue(
                            ScoringRuleCalculationOptionsEnum::CHRONICALLY_ILL_TWO_OR_MORE
                        )->getScore();
                    }
                }
            }
        }

        return 0;
    }

    //could be easily done with enums calculation, once all enums are refactored
    public function genderOfHeadOfHousehold(Household $household, ScoringRule $rule): float
    {
        $hhhGender = $household->getHouseholdHead()->getPerson()->getGender();

        switch ($hhhGender) {
            case PersonGender::FEMALE:
                return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::GENDER_FEMALE)->getScore();

            case PersonGender::MALE:
                return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::GENDER_MALE)->getScore();

            default:
                return 0;
        }
    }

    public function vulnerabilityHeadOfHousehold(Household $household, ScoringRule $rule): float
    {
        $head = $household->getHouseholdHead();

        if ($head->getVulnerabilityCriteria()->isEmpty()) {
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::NO_VULNERABILITY)->getScore();
        }

        $result = 0;

        if ($head->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_DISABLED) ||
            $head->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_CHRONICALLY_ILL)) {
            $result += $rule->getOptionByValue(
                ScoringRuleCalculationOptionsEnum::CHRONICALLY_ILL_OR_DISABLED
            )->getScore();
        }

        if ($head->getAge() !== null && $head->getAge() < 18) {
            $result += $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::INFANT)->getScore();
        }

        if ($head->getAge() !== null && $head->getAge() > 59) {
            $result += $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::ELDERLY)->getScore();
        }

        return $result;
    }

    public function dependencyRatioSyrNWS(Household $household, ScoringRule $rule): float
    {
        $depRatio = $this->dependencyRatioSyr($household);

        if (is_null($depRatio)) {
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_ZERO_DIVISION)->getScore();
        }

        if (Floats::compare($depRatio, 1.5) || $depRatio < 1.5) {
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_NWS_LOW)->getScore();
        }

        return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_NWS_HIGH)->getScore();
    }

    public function dependencyRatioSyrNES(Household $household, ScoringRule $rule): float
    {
        $depRatio = $this->dependencyRatioSyr($household);

        if (is_null($depRatio)) {
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_ZERO_DIVISION)->getScore();
        }

        if (Floats::compare($depRatio, 0)) {
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_NES_0)->getScore();
        } elseif (Floats::compare($depRatio, 1) || $depRatio < 1) {
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_NES_1)->getScore();
        } elseif (Floats::compare($depRatio, 2) || $depRatio < 2) {
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_NES_2)->getScore();
        } elseif (Floats::compare($depRatio, 3) || $depRatio < 3) {
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_NES_3)->getScore();
        } elseif (Floats::compare($depRatio, 4) || $depRatio < 4) {
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_NES_4)->getScore();
        } elseif (Floats::compare($depRatio, 5) || $depRatio < 5) {
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_NES_5)->getScore();
        } else {
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_NES_INF)->getScore();
        }
    }

    private function dependencyRatioSyr(Household $household): ?float
    {
        $childAgeLimit = 17;
        $workingAgeLimit = 60;

        $children = 0;
        $elders = 0;
        $adultsInWorkingAge = 0;

        $adultsWithDisabilities = 0;
        $adultsChronicallyIll = 0;

        foreach ($household->getBeneficiaries() as $member) {
            if (is_null($member->getAge())) {
                continue;
            }

            if ($member->getAge() <= $childAgeLimit) {
                $children++;
            } elseif ($member->getAge() >= $workingAgeLimit) {
                $elders++;
            } else { //the member is adult (in working age)
                $adultsInWorkingAge++;

                if ($member->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_DISABLED)) {
                    $adultsWithDisabilities++;
                }

                if ($member->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_CHRONICALLY_ILL)) {
                    $adultsChronicallyIll++;
                }
            }
        }

        $denominator = $adultsInWorkingAge - $adultsWithDisabilities - $adultsChronicallyIll;

        if ($denominator === 0) {
            return null;
        }

        return ( $children + $elders + $adultsWithDisabilities + $adultsChronicallyIll ) / $denominator;
    }

    public function numberOfOrphans(Household $household, ScoringRule $rule): float
    {
        /** @var CountrySpecificAnswer $countrySpecificAnswer */
        foreach ($household->getCountrySpecificAnswers() as $countrySpecificAnswer) {
            if ($countrySpecificAnswer->getCountrySpecific()->getFieldString() === 'Number of orphans') {
                if ((int)$countrySpecificAnswer->getAnswer() === 0) {
                    return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::NUMBER_OF_ORPHANS_ZERO)->getScore();
                } else {
                    if ((int)$countrySpecificAnswer->getAnswer() > 0) {
                        return $rule->getOptionByValue(
                            ScoringRuleCalculationOptionsEnum::CHRONICALLY_ILL_TWO_OR_MORE
                        )->getScore();
                    }
                }
            }
        }

        return 0;
    }

    //could be easily done with enums calculation, once all enums are refactored
    public function genderOfHeadOfHousehold(Household $household, ScoringRule $rule): float
    {
        $hhhGender = $household->getHouseholdHead()->getPerson()->getGender();

        switch ($hhhGender) {
            case PersonGender::FEMALE:
                return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::GENDER_FEMALE)->getScore();

            case PersonGender::MALE:
                return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::GENDER_MALE)->getScore();

            default:
                return 0;
        }
    }

    public function vulnerabilityHeadOfHousehold(Household $household, ScoringRule $rule): float
    {
        $head = $household->getHouseholdHead();

        $result = 0;

        if (
            $head->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_DISABLED) ||
            $head->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_CHRONICALLY_ILL)
        ) {
            $result += $rule->getOptionByValue(
                ScoringRuleCalculationOptionsEnum::CHRONICALLY_ILL_OR_DISABLED
            )->getScore();
        }

        if ($head->getAge() !== null && $head->getAge() < 18) {
            $result += $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::INFANT)->getScore();
        }

        if ($head->getAge() !== null && $head->getAge() > 59) {
            $result += $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::ELDERLY)->getScore();
        }

        return $result;
    }

    public function dependencyRatioSyr(Household $household, ScoringRule $rule): float
    {
        $childAgeLimit = 17;
        $workingAgeLimit = 60;

        $children = 0;
        $elders = 0;
        $adultsInWorkingAge = 0;

        $adultsWithDisabilitiesOrChronicallyIll = 0;

        foreach ($household->getBeneficiaries() as $member) {
            if (is_null($member->getAge())) {
                continue;
            }

            if ($member->getAge() <= $childAgeLimit) {
                $children++;
            } elseif ($member->getAge() >= $workingAgeLimit) {
                $elders++;
            } else { //the member is adult (in working age)
                $adultsInWorkingAge++;

                if (
                    $member->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_DISABLED) ||
                    $member->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_CHRONICALLY_ILL)
                ) {
                    $adultsWithDisabilitiesOrChronicallyIll++;
                }
            }
        }

        $denominator = $adultsInWorkingAge - $adultsWithDisabilitiesOrChronicallyIll;

        if ($denominator === 0) {
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_ZERO_DIVISION)->getScore();
        }

        $depRatio = ( $children + $elders + $adultsWithDisabilitiesOrChronicallyIll) / $denominator;

        if (Floats::compare($depRatio, 1.5) || $depRatio < 1.5) {
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_LOW)->getScore();
        }

        return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_HIGH)->getScore();
    }

    public function numberOfOrphans(Household $household, ScoringRule $rule): float
    {
        /** @var CountrySpecificAnswer $countrySpecificAnswer */
        foreach ($household->getCountrySpecificAnswers() as $countrySpecificAnswer) {
            if ($countrySpecificAnswer->getCountrySpecific()->getFieldString() === 'Number of orphans') {
                if ((int)$countrySpecificAnswer->getAnswer() === 0) {
                    return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::NUMBER_OF_ORPHANS_ZERO)->getScore();
                } else {
                    if ((int)$countrySpecificAnswer->getAnswer() > 0) {
                        return $rule->getOptionByValue(
                            ScoringRuleCalculationOptionsEnum::CHRONICALLY_ILL_TWO_OR_MORE
                        )->getScore();
                    }
                }
            }
        }

        return 0;
    }

    //could be easily done with enums calculation, once all enums are refactored
    public function genderOfHeadOfHousehold(Household $household, ScoringRule $rule): float
    {
        $hhhGender = $household->getHouseholdHead()->getPerson()->getGender();

        switch ($hhhGender) {
            case PersonGender::FEMALE:
                return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::GENDER_FEMALE)->getScore();

            case PersonGender::MALE:
                return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::GENDER_MALE)->getScore();

            default:
                return 0;
        }
    }

    public function vulnerabilityHeadOfHousehold(Household $household, ScoringRule $rule): float
    {
        $head = $household->getHouseholdHead();

        $result = 0;

        if (
            $head->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_DISABLED) ||
            $head->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_CHRONICALLY_ILL)
        ) {
            $result += $rule->getOptionByValue(
                ScoringRuleCalculationOptionsEnum::CHRONICALLY_ILL_OR_DISABLED
            )->getScore();
        }

        if ($head->getAge() !== null && $head->getAge() < 18) {
            $result += $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::INFANT)->getScore();
        }

        if ($head->getAge() !== null && $head->getAge() > 59) {
            $result += $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::ELDERLY)->getScore();
        }

        return $result;
    }

    public function dependencyRatioSyr(Household $household, ScoringRule $rule): float
    {
        $childAgeLimit = 17;
        $workingAgeLimit = 60;

        $children = 0;
        $elders = 0;
        $adultsInWorkingAge = 0;

        $adultsWithDisabilitiesOrChronicallyIll = 0;

        foreach ($household->getBeneficiaries() as $member) {
            if (is_null($member->getAge())) {
                continue;
            }

            if ($member->getAge() <= $childAgeLimit) {
                $children++;
            } elseif ($member->getAge() >= $workingAgeLimit) {
                $elders++;
            } else { //the member is adult (in working age)
                $adultsInWorkingAge++;

                if (
                    $member->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_DISABLED) ||
                    $member->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_CHRONICALLY_ILL)
                ) {
                    $adultsWithDisabilitiesOrChronicallyIll++;
                }
            }
        }

        $denominator = $adultsInWorkingAge - $adultsWithDisabilitiesOrChronicallyIll;

        if ($denominator === 0) {
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_ZERO_DIVISION)->getScore();
        }

        $depRatio = ( $children + $elders + $adultsWithDisabilitiesOrChronicallyIll) / $denominator;

        if (Floats::compare($depRatio, 1.5) || $depRatio < 1.5) {
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_LOW)->getScore();
        }

        return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_SYR_HIGH)->getScore();
    }
}
