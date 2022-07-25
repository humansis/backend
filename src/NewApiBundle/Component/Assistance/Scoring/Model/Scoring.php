<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring\Model;

use NewApiBundle\Component\Assistance\Scoring\Exception\ScoringRuleNotExist;
use NewApiBundle\Component\Assistance\Scoring\Validator\Scoring as ScoringConstraint;

/**
 * Some complex validation were necessary, so validation of whole Scoring is written in Validator\ScoringValidator.
 * @ScoringConstraint();
 */
final class Scoring
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var ScoringRule[]
     */
    private $rules;

    /**
     * Scoring constructor
     * .
     * @param string $name
     * @param ScoringRule[] $rules
     */
    public function __construct(string $name, array $rules)
    {
        $this->name = $name;
        $this->rules = $rules;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return ScoringRule[]
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @param string $fieldName
     * @return ScoringRule
     *
     * @throws ScoringRuleNotExist
     */
    public function getRuleByFieldName(string $fieldName): ScoringRule
    {
        foreach ($this->rules as $rule) {
            if ($rule->getFieldName() === $fieldName) {
                return $rule;
            }
        }

        throw new ScoringRuleNotExist("Scoring rule with fieldName $fieldName doesn't exist");
    }
}
