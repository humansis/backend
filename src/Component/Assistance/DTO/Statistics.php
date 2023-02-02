<?php

declare(strict_types=1);

namespace Component\Assistance\DTO;

class Statistics
{
    public function __construct(
        private int $id,
        private int $beneficiariesTotal,
        private int $beneficiariesDeleted,
        private int $beneficiariesReached,
        private float | null $amountDistributed = null,
        private float | null $amountTotal = null
    ) {
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return float|null
     */
    public function getAmountDistributed(): ?float
    {
        return $this->amountDistributed;
    }

    /**
     * @param float|null $amountDistributed
     */
    public function setAmountDistributed(?float $amountDistributed): void
    {
        $this->amountDistributed = $amountDistributed;
    }

    /**
     * @return float|null
     */
    public function getAmountTotal(): ?float
    {
        return $this->amountTotal;
    }

    /**
     * @param float|null $amountTotal
     */
    public function setAmountTotal(?float $amountTotal): void
    {
        $this->amountTotal = $amountTotal;
    }

    /**
     * @return int
     */
    public function getBeneficiariesTotal(): int
    {
        return $this->beneficiariesTotal;
    }

    /**
     * @param int $beneficiariesTotal
     */
    public function setBeneficiariesTotal(int $beneficiariesTotal): void
    {
        $this->beneficiariesTotal = $beneficiariesTotal;
    }

    /**
     * @return int
     */
    public function getBeneficiariesDeleted(): int
    {
        return $this->beneficiariesDeleted;
    }

    /**
     * @param int $beneficiariesDeleted
     */
    public function setBeneficiariesDeleted(int $beneficiariesDeleted): void
    {
        $this->beneficiariesDeleted = $beneficiariesDeleted;
    }

    /**
     * @return int
     */
    public function getBeneficiariesReached(): int
    {
        return $this->beneficiariesReached;
    }

    /**
     * @param int $beneficiariesReached
     */
    public function setBeneficiariesReached(int $beneficiariesReached): void
    {
        $this->beneficiariesReached = $beneficiariesReached;
    }

    public function getProgress(): float
    {
        return round($this->getBeneficiariesReached() / $this->getReachedBeneficiariesTotal(), 2);
    }

    public function getReachedBeneficiariesTotal(): int
    {
        return $this->getBeneficiariesTotal() - $this->getBeneficiariesDeleted();
    }
}
