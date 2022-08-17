<?php
declare(strict_types=1);

namespace Component\Assistance\Scoring\Model;

final class ScoringRule
{
    /**
     * One of Enum\ScoringRuleType
     *
     * @var string
     */
    private $type;

    /**
     * $type = countrySpecific: name of country specific option (Entity\CountrySpecific::$fieldString)
     * $type = calculation: name of method which performs the calculation (in RulesCalculation)
     *
     * @var string
     */
    private $fieldName;

    /**
     * @var string
     */
    private $title;

    /**
     * @var ScoringRuleOption[]
     */
    private $options = [];

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

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
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

    /**
     * @param string $value
     *
     * @return ScoringRuleOption
     */
    public function getOptionByValue(string $value): ScoringRuleOption
    {
        foreach ($this->options as $option) {
            if ($option->getValue() === $value) {
                return $option;
            }
        }

        throw new \InvalidArgumentException("Scoring rule {$this->title} does not have option with value $value.");
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }
}
