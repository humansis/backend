<?php

declare(strict_types=1);

namespace Component\Assistance\Scoring;

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
use Component\Assistance\Scoring\Model\ScoringRuleOption;

final class ScoringResolver
{
    /**
     * @var RulesCalculation
     */
    private $customComputation;

    /** @var RulesEnum */
    private $enumResolver;

    /**
     * @var CountrySpecificRepository
     */
    private $countrySpecificRepository;

    /**
     * @var CountrySpecificAnswerRepository
     */
    private $countrySpecificAnswerRepository;

    public function __construct(
        RulesCalculation $customComputation,
        RulesEnum $enumResolver,
        CountrySpecificRepository $countrySpecificRepository,
        CountrySpecificAnswerRepository $countrySpecificAnswerRepository
    ) {
        $this->customComputation = $customComputation;
        $this->enumResolver = $enumResolver;
        $this->countrySpecificRepository = $countrySpecificRepository;
        $this->countrySpecificAnswerRepository = $countrySpecificAnswerRepository;
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
                        $rule->getFieldName(),
                        $rule->getOptions(),
                        $countryCode
                    );
                    break;
                case ScoringRuleType::ENUM:
                    $score = $this->computeEnum($household, $rule);
                    break;
                default:
                    continue 2;
            }

            $protocol->addScore($rule->getTitle(), $score);
        }

        return $protocol;
    }

    private function computeEnum(Household $household, ScoringRule $rule): int
    {
        //todo temporary solution until enums are refactored to be used same style as customComputation
        return $this->enumResolver->getScore($household, $rule);
    }

    /**
     * @param Household $household
     * @param ScoringRule $rule
     *
     * @return int
     */
    private function customComputation(Household $household, ScoringRule $rule): int
    {
        $customComputationReflection = new ReflectionClass(RulesCalculation::class);

        if (!$customComputationReflection->hasMethod($rule->getFieldName())) {
            //TODO zalogovat? dát někam vědět?
            return 0;
        }

        return $this->customComputation->{$rule->getFieldName()}($household, $rule);
    }

    /**
     * @param Household $household
     * @param string $countrySpecificName value of Entity\CountrySpecific::$fieldString for given country
     * @param ScoringRuleOption[] $scoringOptions
     * @param string $countryCode
     *
     * @return int
     */
    private function countrySpecifics(
        Household $household,
        string $countrySpecificName,
        array $scoringOptions,
        string $countryCode
    ): int {
        /** @var CountrySpecific $countrySpecific */
        $countrySpecific = $this->countrySpecificRepository->findOneBy([
            'fieldString' => $countrySpecificName,
            'countryIso3' => $countryCode,
        ]);

        if (!$countrySpecific instanceof CountrySpecific) {
            //TODO zalogovat? dát někam vědět?

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

        foreach ($scoringOptions as $option) {
            if (mb_strtolower($option->getValue()) === mb_strtolower($countrySpecificAnswer->getAnswer())) {
                return $option->getScore();
            }
        }

        return 0;
    }
}
