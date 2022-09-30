<?php declare(strict_types=1);

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
    private $amountPickedUp;

    /**
     * @var float|null
     */
    private $amountSent;

    /**
     * @var float|null
     */
    private $amountTotal;

    /**
     * @var float|null
     */
    private $amountUsed;

    /**
     * @var int
     */
    private $beneficiariesTotal;

    /**
     * @var int
     */
    private $beneficiariesDeleted;

    /**
     * @param int        $id
     * @param int        $beneficiariesTotal
     * @param int        $beneficiariesDeleted
     * @param float|null $amountDistributed
     * @param float|null $amountPickedUp
     * @param float|null $amountSent
     * @param float|null $amountTotal
     * @param float|null $amountUsed
     */
    public function __construct(
        int    $id,
        int    $beneficiariesTotal,
        int    $beneficiariesDeleted,
        ?float $amountDistributed = null,
        ?float $amountPickedUp = null,
        ?float $amountSent = null,
        ?float $amountTotal = null,
        ?float $amountUsed = null
    ) {
        $this->id = $id;
        $this->beneficiariesTotal = $beneficiariesTotal;
        $this->beneficiariesDeleted = $beneficiariesDeleted;
        $this->amountDistributed = $amountDistributed;
        $this->amountPickedUp = $amountPickedUp;
        $this->amountSent = $amountSent;
        $this->amountTotal = $amountTotal;
        $this->amountUsed = $amountUsed;
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
    public function getAmountPickedUp(): ?float
    {
        return $this->amountPickedUp;
    }

    /**
     * @param float|null $amountPickedUp
     */
    public function setAmountPickedUp(?float $amountPickedUp): void
    {
        $this->amountPickedUp = $amountPickedUp;
    }

    /**
     * @return float|null
     */
    public function getAmountSent(): ?float
    {
        return $this->amountSent;
    }

    /**
     * @param float|null $amountSent
     */
    public function setAmountSent(?float $amountSent): void
    {
        $this->amountSent = $amountSent;
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
     * @return float|null
     */
    public function getAmountUsed(): ?float
    {
        return $this->amountUsed;
    }

    /**
     * @param float|null $amountUsed
     */
    public function setAmountUsed(?float $amountUsed): void
    {
        $this->amountUsed = $amountUsed;
    }

}
