<?php declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\DTO;

class CommoditySummary
{
    /** @var string */
    private $modalityType;
    /** @var float */
    private $amount;
    /** @var string */
    private $unit;

    /**
     * @param string $modalityType
     * @param string $unit
     */
    public function __construct(string $modalityType, string $unit)
    {
        $this->modalityType = $modalityType;
        $this->unit = $unit;
    }

    public function addAmount(float $amount): void
    {
        $this->amount += $amount;
    }

    /**
     * @return string
     */
    public function getModalityType(): string
    {
        return $this->modalityType;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getUnit(): string
    {
        return $this->unit;
    }

}
