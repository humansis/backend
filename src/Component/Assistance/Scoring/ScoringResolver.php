<?php

declare(strict_types=1);

namespace Component\Assistance\Scoring;

use Component\Assistance\Scoring\Enum\ScoringSupportedHouseholdCoreFieldsEnum;
use Entity\CountrySpecific;
use Entity\CountrySpecificAnswer;
use Entity\Household;
use ReflectionClass;
use Repository\CountrySpecificAnswerRepository;
use Repository\CountrySpecificRepository;
use Component\Assistance\Scoring\Enum\ScoringRuleType;
use Component\Assistance\Scoring\Model\ScoringProtocol;
use Component\Assistance\Scoring\Model\Scoring;
use Component\Assistance\Scoring\Model\ScoringRule;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class ScoringResolver
{
    public function __construct(
        private readonly RulesCalculation $customComputation,
        private readonly RulesEnum $enumResolver,
        private readonly CountrySpecificRepository $countrySpecificRepository,
        private readonly CountrySpecificAnswerRepository $countrySpecificAnswerRepository
    ) {
    }

    public function compute(Household $household, Scoring $scoring, string $countryCode): ScoringProtocol
    {
        $protocol = new ScoringProtocol();

        foreach ($scoring->getRules() as $rule) {
            switch ($rule->getType()) {
                case ScoringRuleType::CALCULATION:
                    $score = $this->customComputation($household, $rule);
                    break;
                case ScoringRuleType::COUNTRY_SPECIFIC:
                    $score = $this->countrySpecifics(
                        $household,
                        $rule,
                        $countryCode
                    );
                    break;
                case ScoringRuleType::ENUM:
                    $score = $this->computeEnum($household, $rule);
                    break;
                case ScoringRuleType::CORE_HOUSEHOLD:
                    $score = $this->computeCoreHousehold($household, $rule);
                    break;
                case ScoringRuleType::COMPUTED_VALUE:
                    $score = $this->computeSimpleCalculation($household, $rule);
                    break;
                default:
                    continue 2;
            }

            $protocol->addScore($rule->getTitle(), $score);
        }

        return $protocol;
    }

    private function computeEnum(Household $household, ScoringRule $rule): float
    {
        //todo temporary solution until enums are refactored to be used same style as customComputation
        return $this->enumResolver->getScore($household, $rule);
    }

    private function customComputation(Household $household, ScoringRule $rule): float
    {
        $customComputationReflection = new ReflectionClass(RulesCalculation::class);

        if (!$customComputationReflection->hasMethod($rule->getFieldName())) {
            return 0;
        }

        return $this->customComputation->{$rule->getFieldName()}($household, $rule);
    }

    private function countrySpecifics(
        Household $household,
        ScoringRule $rule,
        string $countryCode
    ): float {
        /** @var CountrySpecific $countrySpecific */
        $countrySpecific = $this->countrySpecificRepository->findOneBy([
            'fieldString' => $rule->getFieldName(),
            'countryIso3' => $countryCode,
        ]);

        // Country specific option does not exist
        if (!$countrySpecific instanceof CountrySpecific) {
            return 0;
        }

        $countrySpecificAnswer = $this->countrySpecificAnswerRepository->findOneBy([
            'countrySpecific' => $countrySpecific,
            'household' => $household,
        ]);

        // Household does not have filled the country specific option
        if (!$countrySpecificAnswer instanceof CountrySpecificAnswer) {
            return 0;
        }

        $CSOValue = $countrySpecificAnswer->getAnswer();

        if ($countrySpecific->getType() === 'number') {
            $CSOValue = (int) $CSOValue;
        }

        return $this->evaluateOptionExpression($rule, $CSOValue);
    }

    private function computeSimpleCalculation(Household $household, ScoringRule $rule): float
    {
        $simpleCalculationReflection = new ReflectionClass(ScoringComputedValues::class);

        if (!$simpleCalculationReflection->hasMethod($rule->getFieldName())) {
            return 0;
        }

        $simpleCalculation = new ScoringComputedValues();

        $value = $simpleCalculation->{$rule->getFieldName()}($household);

        if (is_null($value)) {
            return 0;
        }

        return $this->evaluateOptionExpression($rule, $value);
    }

    private function computeCoreHousehold(Household $household, ScoringRule $rule): float
    {
        $value = match ($rule->getFieldName()) {
            ScoringSupportedHouseholdCoreFieldsEnum::NOTES => $household->getNotes(),
            ScoringSupportedHouseholdCoreFieldsEnum::INCOME => $household->getIncome(),
            ScoringSupportedHouseholdCoreFieldsEnum::FOOD_CONSUMPTION_SCORE => $household->getFoodConsumptionScore(),
            ScoringSupportedHouseholdCoreFieldsEnum::COPING_STRATEGIES_INDEX => $household->getCopingStrategiesIndex(),
            ScoringSupportedHouseholdCoreFieldsEnum::DEBT_LEVEL => $household->getDebtLevel(),
            ScoringSupportedHouseholdCoreFieldsEnum::INCOME_SPENT_ON_FOOD => $household->getIncomeSpentOnFood(),
            ScoringSupportedHouseholdCoreFieldsEnum::HOUSEHOLD_INCOME => $household->getHouseholdIncome(),
            ScoringSupportedHouseholdCoreFieldsEnum::ASSETS => $household->getAssets(),
            ScoringSupportedHouseholdCoreFieldsEnum::SUPPORT_RECEIVED_TYPES => $household->getSupportReceivedTypes(),
            default => null,
        };

        if (is_null($value)) {
            return 0;
        }

        //if value from Household is an array
        if (is_array($value)) {
            $score = 0;

            foreach ($value as $valueItem) {
                if (is_null($valueItem)) {
                    continue;
                }

                $score += $this->evaluateOptionExpression($rule, $valueItem);
            }

            return $score;
        }

        //the value here could be only string or int
        return $this->evaluateOptionExpression($rule, $value);
    }

    /**
     * @param ScoringRule $rule
     * @param int|string $value value which is compared to rule options
     *
     * @return float Final score for given rule
     */
    private function evaluateOptionExpression(ScoringRule $rule, int|string $value): float
    {
        $expressionLanguage = new ExpressionLanguage();

        foreach ($rule->getOptions() as $option) {
            if (is_int($value)) {
                // if the expression contains 'x' then evaluate it as expression
                if (str_contains($option->getValue(), 'x')) {
                    $result = $expressionLanguage->evaluate($option->getValue(), [
                        'x' => $value,
                    ]);

                    if ($result === true) {
                        return $option->getScore();
                    }
                } else { // if the expression does not contain 'x' then evaluate it as number
                    $result = $expressionLanguage->evaluate($option->getValue());

                    if ($value === $result) {
                        return $option->getScore();
                    }
                }
            } elseif (mb_strtolower($option->getValue()) === mb_strtolower($value)) {
                // if the value is string, just compare if it is equal to option value
                return $option->getScore();
            }
        }

        return 0;
    }
}
