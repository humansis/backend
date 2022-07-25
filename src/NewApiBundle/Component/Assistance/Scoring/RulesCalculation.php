<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring;

use BeneficiaryBundle\Entity\Beneficiary;
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
            $memberGender = $beneficiary->getPerson()->getGender();
            $memberAge = $beneficiary->getPerson()->getAge();

            $totalScore += $this->memberScoring($beneficiary, $memberGender, $memberAge, $rule);
        }

        return $totalScore;
    }

    private function memberScoring(Beneficiary $beneficiary, $gender, $age, ScoringRule $rule): int
    {
        $result = 0;
        switch (true) {
            case $beneficiary->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_NO_VULNERABILITY):
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::NO_VULNERABILITY)->getScore();
                break;

            case $beneficiary->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_DISABLED):
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::DISABLED)->getScore();
                break;

            case $beneficiary->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_CHRONICALLY_ILL):
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::CHRONICALLY_ILL)->getScore();
                break;

            case $gender === PersonGender::FEMALE &&
                ($beneficiary->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_PREGNANT) ||
                 $beneficiary->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_LACTATING)):
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::PREGNANT_LACTATING_FEMALE)->getScore();
                break;

            case $age < 18:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::AGE_18)->getScore();
                break;

            case $age > 59:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::AGE_59)->getScore();
                break;

            default:
                break;
        }

        return $result;
    }

    public function shelterType(Household $household, ScoringRule $rule): int
    {
        $shelterStatus = $household->getShelterStatus();
        return $rule->getOptionByValue($shelterStatus)->getScore();
    }

    public function assets(Household $household, ScoringRule $rule): int
    {
        $assetsNum = count($household->getAssets());
        switch ($assetsNum) {
            case 0:
            case 1:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::ASSETS_0_1)->getScore();
                break;

            case 2:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::ASSETS_2)->getScore();
                break;

            case 3:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::ASSETS_3)->getScore();
                break;

            case 4:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::ASSETS_4)->getScore();
                break;

            default:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::ASSETS_5_MORE)->getScore();
                break;
        }

        return $result;
    }

    public function csi(Household $household, ScoringRule $rule): int
    {
        $csi = $household->getCopingStrategiesIndex();

        switch (true) {
            case $csi < 20:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::CSI_0_20)->getScore();
                break;

            case $csi < 30:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::CSI_20_30)->getScore();
                break;

            case $csi < 40:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::CSI_30_40)->getScore();
                break;

            default:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::CSI_40_MORE)->getScore();
                break;

        }

        return $result;
    }

    public function incomeSpentOnFood(Household $household, ScoringRule $rule): int
    {
        $isf = $household->getIncomeSpentOnFood();
        $income = $household->getIncome();
        $isfRatio = $isf / $income * 100;

        switch (true) {
            case $isfRatio < 50:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::INCOME_SPENT_0_50)->getScore();
                break;

            case $isfRatio < 65:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::INCOME_SPENT_50_65)->getScore();
                break;

            case $isfRatio < 75:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::INCOME_SPENT_65_75)->getScore();
                break;

            default:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::INCOME_SPENT_75_MORE)->getScore();
                break;
        }

        return $result;
    }

    public function fcs(Household $household, ScoringRule $rule): int
    {
        $fcs = $household->getFoodConsumptionScore();

        switch (true) {
            case $fcs <= 21:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::CONSUMPTION_POOR)->getScore();
                break;

            case $fcs <= 35:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::CONSUMPTION_BORDERLINE)->getScore();
                break;

            default:
                $result = $rule->getOptionByValue(ScoringRuleOptionsEnum::CONSUMPTION_ACCEPTABLE)->getScore();
                break;
        }

        return $result;
    }
}
