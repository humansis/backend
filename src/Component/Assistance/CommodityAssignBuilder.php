<?php

declare(strict_types=1);

namespace Component\Assistance;

use Entity\AssistanceBeneficiary;
use Entity\Commodity;

class CommodityAssignBuilder
{
    private $fixedValues = [];

    private $callbacks = [];

    public function addCommodityValue(string $modality, string $unit, float $value): void
    {
        if (!isset($this->fixedValues[$modality])) {
            $this->fixedValues[$modality] = [];
        }
        if (!isset($this->fixedValues[$modality][$unit])) {
            $this->fixedValues[$modality][$unit] = 0;
        }
        $this->fixedValues[$modality][$unit] += $value;
    }

    public function addCommodityCallback(string $modality, string $unit, callable $callback): void
    {
        if (!isset($this->callbacks[$modality])) {
            $this->callbacks[$modality] = [];
        }
        if (!isset($this->callbacks[$modality][$unit])) {
            $this->callbacks[$modality][$unit] = [];
        }
        $this->callbacks[$modality][$unit][] = $callback;
    }

    public function getValue(AssistanceBeneficiary $target, string $modality, string $unit): float
    {
        $sum = 0;
        if (isset($this->fixedValues[$modality][$unit])) {
            $sum += $this->fixedValues[$modality][$unit];
        }
        if (isset($this->callbacks[$modality][$unit])) {
            foreach ($this->callbacks[$modality][$unit] as $callback) {
                $sum += $callback($target);
            }
        }

        return $sum;
    }
}
