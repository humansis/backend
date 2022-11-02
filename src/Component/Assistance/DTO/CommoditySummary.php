<?php

declare(strict_types=1);

namespace Component\Assistance\DTO;

class CommoditySummary
{
    public function __construct(private readonly string $modalityType, private readonly string $unit, private readonly float $amount = 0)
    {
    }

    public function getModalityType(): string
    {
        return $this->modalityType;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }
}
