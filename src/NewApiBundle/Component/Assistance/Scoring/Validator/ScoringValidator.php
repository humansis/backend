<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring\Validator;

use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRuleCalculationOptionsEnum;
use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRulesCalculationsEnum;
use NewApiBundle\Component\Assistance\Scoring\Enum\ScoringRuleType;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringRuleOption;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class ScoringValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (is_null($value)) {
            return;
        }

        if (!$value instanceof \NewApiBundle\Component\Assistance\Scoring\Model\Scoring) {
            throw new \InvalidArgumentException('Scoring validation constraint can be used only with ' . \NewApiBundle\Component\Assistance\Scoring\Model\Scoring::class . ' class.');
        }

        foreach ($value->getRules() as $rule) {
            if (!in_array($rule->getType(), ScoringRuleType::values())) {
                $this->context->buildViolation("Rule type {$rule->getType()} is not supported. Supported values are [" . implode(', ', ScoringRuleType::values()))
                    ->addViolation();
            }

            if ($rule->getType() === ScoringRuleType::CALCULATION) {

                if (!in_array($rule->getFieldName(), ScoringRulesCalculationsEnum::values())) {
                    $this->context->buildViolation("No calculation rule with Field Name {$rule->getFieldName()} is supported. Supported values are: [" . implode(', ', ScoringRulesCalculationsEnum::values()) . ']')
                        ->addViolation();

                    continue;
                }

                $supportedOptions = ScoringRuleCalculationOptionsEnum::SUPPORTED[$rule->getFieldName()];
            } else {
                continue;
            }

            foreach ($rule->getOptions() as $option) {
                if (!in_array($option->getValue(), $supportedOptions)) {
                    $this->context->buildViolation("Option '{$option->getValue()}' is not allowed for rule '{$rule->getTitle()}'. Supported options are [" . implode(', ', $supportedOptions) . ']')
                        ->addViolation();
                }
            }

            $optionsInCsv = array_map(function (ScoringRuleOption $option) {
                return $option->getValue();
            }, $rule->getOptions());

            $missingOptions = array_diff($supportedOptions, $optionsInCsv);

            if (!empty($missingOptions)) {
                $this->context->buildViolation("Rule {$rule->getTitle()} does not have all required options. These options are missing: [" . implode(', ', $missingOptions) . ']')
                    ->addViolation();
            }
        }
    }
}
