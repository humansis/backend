<?php

declare(strict_types=1);

namespace Component\Assistance\Scoring\Model;

use Component\Assistance\Scoring\Validator\Scoring as ScoringConstraint;

#[ScoringConstraint]
final class Scoring
{
    /**
     * Scoring constructor
     *
     * @param ScoringRule[] $rules
     */
    public function __construct(
        private readonly string $name,
        private readonly array $rules
    ) {
    }

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
}
