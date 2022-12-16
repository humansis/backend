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
    public function __construct(private readonly RulesCalculation $customComputation, private readonly RulesEnum $enumResolver, private readonly CountrySpecificRepository $countrySpecificRepository, private readonly CountrySpecificAnswerRepository $countrySpecificAnswerRepository)
    {
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
            //TODO zalogovat? dát někam vědět?
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
        $simpleCalculationReflection = new ReflectionClass(SimpleCalculations::class);

        if (!$simpleCalculationReflection->hasMethod($rule->getFieldName())) {
            return 0;
        }

        $simpleCalculation = new SimpleCalculations();

        $value = $simpleCalculation->{$rule->getFieldName()}($household);

        if (is_null($value)) {
            return 0;
        }

        return $this->evaluateOptionExpression($rule, $value);
    }

    private function computeCoreHousehold(Household $household, ScoringRule $rule): float
    {
        switch ($rule->getFieldName()) {
            case ScoringSupportedHouseholdCoreFieldsEnum::NOTES:
                $value = $household->getNotes();
                break;
            case ScoringSupportedHouseholdCoreFieldsEnum::INCOME:
                $value = $household->getIncome();
                break;
            case ScoringSupportedHouseholdCoreFieldsEnum::FOOD_CONSUMPTION_SCORE:
                $value = $household->getFoodConsumptionScore();
                break;
            case ScoringSupportedHouseholdCoreFieldsEnum::COPING_STRATEGIES_INDEX:
                $value = $household->getCopingStrategiesIndex();
                break;
            case ScoringSupportedHouseholdCoreFieldsEnum::DEBT_LEVEL:
                $value = $household->getDebtLevel();
                break;
            case ScoringSupportedHouseholdCoreFieldsEnum::INCOME_SPENT_ON_FOOD:
                $value = $household->getIncomeSpentOnFood();
                break;
            case ScoringSupportedHouseholdCoreFieldsEnum::HOUSEHOLD_INCOME:
                $value = $household->getHouseholdIncome();
                break;
            case ScoringSupportedHouseholdCoreFieldsEnum::ASSETS:
                $value = $household->getAssets();
                break;
            case ScoringSupportedHouseholdCoreFieldsEnum::SUPPORT_RECEIVED_TYPES:
                $value = $household->getSupportReceivedTypes();
                break;
            default:
                return 0;
        }

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

                    if (is_bool($result) && $result) {
                        return $option->getScore();
                    }
                } else { // if the expression does not contain 'x' then evaluate it as number
                    $result = $expressionLanguage->evaluate($option->getValue());

                    if ($value === $result) {
                        return $option->getScore();
                    }
                }
            } else {
                // if the value is string, just compare if it is equal to option value
                if (mb_strtolower($option->getValue()) === mb_strtolower($value)) {
                    return $option->getScore();
                }
            }
        }

        return 0;
    }
}
