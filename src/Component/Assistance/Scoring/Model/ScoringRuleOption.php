<?php

declare(strict_types=1);

namespace Component\Assistance\Scoring\Model;

final class ScoringRuleOption
{
    /**
     * @var string
     */
    private $value;

    /**
     * @var float
     */
    private $score;

    public function __construct(string $value, float $score)
    {
        $this->value = $value;
        $this->score = $score;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getScore(): float
    {
        return $this->score;
    }
}
