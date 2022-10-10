<?php

declare(strict_types=1);

namespace Component\Assistance\DTO;

class Statistics
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var float|null
     */
    private $amountDistributed;

    /**
     * @var float|null
     */
    private $amountTotal;

    /**
     * @var int
     */
    private $beneficiariesTotal;

    /**
     * @var int
     */
    private $beneficiariesDeleted;

    /**
     * @var int
     */
    private $beneficiariesReached;

    /**
     * @param int $id
     * @param int $beneficiariesTotal
     * @param int $beneficiariesDeleted
     * @param int $beneficiariesReached
     * @param float|null $amountDistributed
     * @param float|null $amountTotal
     */
    public function __construct(
        int $id,
        int $beneficiariesTotal,
        int $beneficiariesDeleted,
        int $beneficiariesReached,
        ?float $amountDistributed = null,
        ?float $amountTotal = null
    ) {
        $this->id = $id;
        $this->beneficiariesTotal = $beneficiariesTotal;
        $this->beneficiariesDeleted = $beneficiariesDeleted;
        $this->amountDistributed = $amountDistributed;
        $this->amountTotal = $amountTotal;
        $this->beneficiariesReached = $beneficiariesReached;
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
}
