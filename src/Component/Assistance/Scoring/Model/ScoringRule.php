<?php

declare(strict_types=1);

namespace Component\Assistance\Scoring\Model;

use InvalidArgumentException;

final class ScoringRule
{
    /**
     * One of Enum\ScoringRuleType
     */
    private string $type;

    /**
     * $type = countrySpecific: name of country specific option (Entity\CountrySpecific::$fieldString)
     * $type = calculation: name of method which performs the calculation (in RulesCalculation)
     * $type = enum: name of enum class
     * $type = coreHousehold: name of attribute of Household entity. (Supported fields are in ScoringSupportedHouseholdCoreFieldsEnum)
     */
    private string $fieldName;

    private string $title;

    /**
     * @var ScoringRuleOption[]
     */
    private array $options = [];

    public function __construct(string $type, string $fieldName, string $title)
    {
        $this->type = $type;
        $this->fieldName = $fieldName;
        $this->title = $title;
    }

    public function addOption(ScoringRuleOption $option): void
    {
        $this->options[] = $option;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * @return ScoringRuleOption[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOptionByValue(string $value): ScoringRuleOption
    {
        foreach ($this->options as $option) {
            if ($option->getValue() === $value) {
                return $option;
            }
        }

        throw new InvalidArgumentException("Scoring rule {$this->title} does not have option with value $value.");
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
