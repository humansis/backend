<?php

declare(strict_types=1);

namespace Component\Assistance\Scoring\Model;

final class ScoringRuleOption
{
    public function __construct(private readonly string $value, private readonly float $score)
    {
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
