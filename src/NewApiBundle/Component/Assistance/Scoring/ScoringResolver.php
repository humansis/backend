<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring;

use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Repository\CountrySpecificAnswerRepository;
use BeneficiaryBundle\Repository\CountrySpecificRepository;
use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRuleType;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringProtocol;
use NewApiBundle\Component\Assistance\Scoring\Model\Scoring;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRule;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRuleOption;

final class ScoringResolver
{
    /**
     * @var RulesCalculation
     */
    private $customComputation;

    /**
     * @var CountrySpecificRepository
     */
    private $countrySpecificRepository;

    /**
     * @var CountrySpecificAnswerRepository
     */
    private $countrySpecificAnswerRepository;

    public function __construct(RulesCalculation $customComputation, CountrySpecificRepository $countrySpecificRepository, CountrySpecificAnswerRepository $countrySpecificAnswerRepository)
    {
        $this->customComputation = $customComputation;
        $this->countrySpecificRepository = $countrySpecificRepository;
        $this->countrySpecificAnswerRepository = $countrySpecificAnswerRepository;
    }

    public function compute(Household $household, Scoring $scoring, string $countryCode): ScoringProtocol
    {
        $protocol = new ScoringProtocol();

        foreach ($scoring->getRules() as $rule) {
            if ($rule->getType() === ScoringRuleType::CALCULATION) {
                $score = $this->customComputation($household, $rule);
            } else if ($rule->getType() === ScoringRuleType::COUNTRY_SPECIFIC) {
                $score = $this->countrySpecifics($household, $rule->getFieldName(), $rule->getOptions(), $countryCode);
            } else {
                continue;
            }

            $protocol->addScore($rule->getTitle(), $score);
        }

        return $protocol;
    }

    /**
     * @param Household $household
     * @param ScoringRule $rule
     *
     * @return int
     */
    private function customComputation(Household $household, ScoringRule $rule): int
    {
        $customComputationReflection = new \ReflectionClass(RulesCalculation::class);

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
    private function countrySpecifics(Household $household, string $countrySpecificName, array $scoringOptions, string $countryCode): int
    {
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

        foreach ($scoringOptions as $option) {
            if ($option->getValue() === $countrySpecificAnswer->getAnswer()) {
                return $option->getScore();
            }
        }

        return 0;
    }
}
