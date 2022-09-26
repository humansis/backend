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
     * @var int
     */
    private $beneficiariesTotal;

    /**
     * @var int
     */
    private $beneficiariesDeleted;

    /**
     * @param int        $id
     * @param float|null $amountDistributed
     * @param float|null $amountPickedUp
     * @param float|null $amountSent
     * @param float|null $amountTotal
     * @param int        $beneficiariesTotal
     * @param int        $beneficiariesDeleted
     *
     * @return Statistics
     */
    public static function create(
        int    $id,
        ?float $amountDistributed,
        ?float $amountPickedUp,
        ?float $amountSent,
        ?float $amountTotal,
        int    $beneficiariesTotal,
        int    $beneficiariesDeleted
    ): Statistics {
        $self = new self();
        $self->setId($id);
        $self->setAmountDistributed($amountDistributed);
        $self->setAmountPickedUp($amountPickedUp);
        $self->setAmountSent($amountSent);
        $self->setAmountTotal($amountTotal);
        $self->setBeneficiariesTotal($beneficiariesTotal);
        $self->setBeneficiariesDeleted($beneficiariesDeleted);

        return $self;
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

}
