<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRuleCalculationOptionsEnum;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRule;
use NewApiBundle\Enum\HouseholdShelterStatus;
use NewApiBundle\Utils\Floats;
use NewApiBundle\Enum\PersonGender;

/**
 * All methods needs to be public function(Household $household, ScoringRule $rule): int
 *
 * Every public method in this class needs to be included in ScoringRuleCalculationOptionsEnum. Also every value in
 * ScoringRuleCalculationOptionsEnum has to have implementation in this class.
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
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_HIGH)->getScore();
        }

        $dependencyRatio = ($children + $elders) / $adultsInWorkingAge;

        if (Floats::compare($dependencyRatio, 1.0)) {
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_MID)->getScore();
        } else if ($dependencyRatio > 1.0) {
            return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEPENDENCY_RATIO_HIGH)->getScore();
        }

        return 0;
    }

    /**
     * Dependency ratio will be adjusted and finished within issue https://jira.quanti.cz/browse/PIN-3622
     *
     * @param Household $household
     * @param ScoringRule $rule
     * @return int
     */
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

                case $age >= 18 && $age < 60 && $isChronicallyIll:
                    $adultsWorking++;
                    $adultsChronicallyIll++;
                    break;

                case $age >= 18 && $age < 60 && $isDisabled:
                    $adultsWorking++;
                    $adultsDisabled++;
                    break;

                case $age >= 18 && $age < 60:
                    $adultsWorking++;
                    break;

                case $age >= 60:
                    $adultsOver60++;
            }
        }

        $ratio = $adultsWorking > 0 ? ((float)($children + $adultsOver60 + $adultsDisabled + $adultsChronicallyIll)) / $adultsWorking : 0;

        switch (true) {
            case $ratio > 0 && $ratio <= 1:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::VERY_LOW_VULNERABILITY)->getScore();
                break;

            case Floats::compare($ratio, 2):
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::LOW_VULNERABILITY)->getScore();
                break;

            case Floats::compare($ratio, 3):
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::MODERATE_VULNERABILITY)->getScore();
                break;

            case Floats::compare($ratio, 4):
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::HIGH_VULNERABILITY)->getScore();
                break;

            case Floats::compare($ratio, 5):
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::VERY_HIGH_VULNERABILITY)->getScore();
                break;

            case $ratio === 0 || $ratio >= 6:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::EXTREME_VULNERABILITY)->getScore();
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
                return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::VULNERABILITY_SOLO_PARENT)->getScore();
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
                    $totalScore += $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::VULNERABILITY_PREGNANT_OR_LACTATING)->getScore();
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
                    return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::CHRONICALLY_ILL_ONE)->getScore();
                } else if ( (int) $countrySpecificAnswer->getAnswer() > 1) {
                    return $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::CHRONICALLY_ILL_TWO_OR_MORE)->getScore();
                }
            }
        }

        return 0;
    }

    public function hhHeadGender(Household $household, ScoringRule $rule): int
    {
        $hhhGender = $household->getHouseholdHead()->getPerson()->getGender();

        switch ($hhhGender) {
            case PersonGender::FEMALE:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::GENDER_FEMALE)->getScore();
                break;

            case PersonGender::MALE:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::GENDER_MALE)->getScore();
                break;

            default:
                $result = 0;
        }

        return $result;
    }

    public function hhHeadVulnerability(Household $household, ScoringRule $rule): int
    {
        $hhhGender = $household->getHouseholdHead()->getPerson()->getGender();
        $hhhAge = $household->getHouseholdHead()->getPerson()->getAge();
        $hhhVulnerabilityCriteria = $household->getHouseholdHead();

        return $this->memberScoring($hhhVulnerabilityCriteria, $hhhGender, $hhhAge, $rule);
    }

    public function hhMembersVulnerability(Household $household, ScoringRule $rule): int
    {
        $beneficiaries = $household->getBeneficiaries();

        $totalScore = 0;
        foreach ($beneficiaries as $beneficiary) {
            if($beneficiary->isHead() === false) {
                $memberGender = $beneficiary->getPerson()->getGender();
                $memberAge = $beneficiary->getPerson()->getAge();

                $totalScore += $this->memberScoring($beneficiary, $memberGender, $memberAge, $rule);
            }
        }

        return $totalScore;
    }

    private function memberScoring(Beneficiary $beneficiary, string $gender, int $age, ScoringRule $rule): int
    {
        $result = 0;
        if($beneficiary->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_DISABLED)) {
            $result += $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DISABLED)->getScore();
        }

        if($beneficiary->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_CHRONICALLY_ILL)) {
            $result += $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::CHRONICALLY_ILL)->getScore();
        }

        if($gender === PersonGender::FEMALE &&
            ($beneficiary->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_PREGNANT) ||
                $beneficiary->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_LACTATING))) {
            $result += $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::PREGNANT_LACTATING_FEMALE)->getScore();
        }

        if($age < 18) {
            $result += $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::INFANT)->getScore();
        }

        if($age > 59) {
            $result += $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::ELDERLY)->getScore();
        }

        if($beneficiary->hasVulnerabilityCriteria(VulnerabilityCriterion::CRITERION_NO_VULNERABILITY)) {
            $result += $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::NO_VULNERABILITY)->getScore();
        }

        if($result === 0) {
            $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::OTHER)->getScore();
        }

        return $result;
    }

    public function shelterType(Household $household, ScoringRule $rule): int
    {
        $shelterStatus = $household->getShelterStatus();
        switch ($shelterStatus) {
            case HouseholdShelterStatus::TENT:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::SHELTER_TENT)->getScore();
                break;

            case HouseholdShelterStatus::MAKESHIFT_SHELTER:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::SHELTER_MAKESHIFT)->getScore();
                break;

            case HouseholdShelterStatus::TRANSITIONAL_SHELTER:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::SHELTER_TRANSITIONAL)->getScore();
                break;

            case HouseholdShelterStatus::HOUSE_APARTMENT_SEVERELY_DAMAGED:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::SHELTER_SEVERELY_DAMAGED)->getScore();
                break;

            case HouseholdShelterStatus::HOUSE_APARTMENT_MODERATELY_DAMAGED:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::SHELTER_MODERATELY_DAMAGED)->getScore();
                break;

            case HouseholdShelterStatus::HOUSE_APARTMENT_NOT_DAMAGED:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::SHELTER_NOT_DAMAGED)->getScore();
                break;

            case HouseholdShelterStatus::ROOM_OR_SPACE_IN_PUBLIC_BUILDING:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::SHELTER_SHARED)->getScore();
                break;

            default:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::SHELTER_OTHER)->getScore();
                break;
        }

        return $result;

    }

    public function productiveAssets(Household $household, ScoringRule $rule): int
    {
        $assetsNum = count($household->getAssets());
        switch ($assetsNum) {
            case 0:
            case 1:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::ASSETS_0_1)->getScore();
                break;

            case 2:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::ASSETS_2)->getScore();
                break;

            case 3:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::ASSETS_3)->getScore();
                break;

            case 4:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::ASSETS_4)->getScore();
                break;

            default:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::ASSETS_5_MORE)->getScore();
                break;
        }

        return $result;
    }

    public function csi(Household $household, ScoringRule $rule): int
    {
        $csi = $household->getCopingStrategiesIndex();

        switch (true) {
            case $csi < 20:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::CSI_0_20)->getScore();
                break;

            case $csi < 30:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::CSI_20_30)->getScore();
                break;

            case $csi < 40:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::CSI_30_40)->getScore();
                break;

            default:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::CSI_40_MORE)->getScore();
                break;

        }

        return $result;
    }

    public function incomeSpentOnFood(Household $household, ScoringRule $rule): int
    {
        $isf = $household->getIncomeSpentOnFood();
        $income = $household->getIncome();
        $isfRatio = $isf * 100 / $income;

        switch (true) {
            case $isfRatio < 50:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::INCOME_SPENT_0_50)->getScore();
                break;

            case $isfRatio < 65:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::INCOME_SPENT_50_65)->getScore();
                break;

            case $isfRatio < 75:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::INCOME_SPENT_65_75)->getScore();
                break;

            default:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::INCOME_SPENT_75_MORE)->getScore();
                break;
        }

        return $result;
    }

    public function fcs(Household $household, ScoringRule $rule): int
    {
        $fcs = $household->getFoodConsumptionScore();

        switch (true) {
            case $fcs < 21:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::CONSUMPTION_POOR)->getScore();
                break;

            case $fcs < 36:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::CONSUMPTION_BORDERLINE)->getScore();
                break;

            default:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::CONSUMPTION_ACCEPTABLE)->getScore();
                break;
        }

        return $result;
    }

    public function debt(Household $household, ScoringRule $rule): int
    {
        $debtLevel = $household->getDebtLevel();

        switch ($debtLevel) {
            case 1:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEBT_LEVEL_1)->getScore();
                break;

            case 2:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEBT_LEVEL_2)->getScore();
                break;

            case 3:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEBT_LEVEL_3)->getScore();
                break;

            case 4:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEBT_LEVEL_4)->getScore();
                break;

            case 5:
                $result = $rule->getOptionByValue(ScoringRuleCalculationOptionsEnum::DEBT_LEVEL_5)->getScore();
                break;

            default:
                $result = 0;
        }

        return $result;
    }

    public function assistanceProvided(Household $household, ScoringRule $rule): int
    {
        $receivedTypes = $household->getSupportReceivedTypes();
        $supportDateReceived = $household->getSupportDateReceived();
        $today = new \DateTime('now');
        $months = $today->diff($supportDateReceived)->m;

        $result = 0;
        if ($months < 3) {
            foreach ($receivedTypes as $type) {
                $result += $rule->getOptionByValue($type)->getScore();
            }
        }

        return $result;
    }
}