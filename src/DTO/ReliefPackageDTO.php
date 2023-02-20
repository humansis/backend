<?php

declare(strict_types=1);

namespace DTO;

use DateTimeInterface;

class ReliefPackageDTO
{
    public function __construct(
        private readonly int $id,
        private readonly string $state,
        private readonly string $modalityType,
        private readonly string | null $notes,
        private readonly string $amountDistributed,
        private readonly string $amountToDistribute,
        private readonly string $unit,
        private readonly DateTimeInterface $createdAt,
        private readonly DateTimeInterface $lastModifiedAt,
        private readonly DateTimeInterface | null $distributedAt
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getModalityType(): string
    {
        return $this->modalityType;
    }

    public function getNotes(): string
    {
        return (string) $this->notes;
    }

    public function getAmountDistributed(): string
    {
        return $this->amountDistributed;
    }

    public function getAmountToDistribute(): string
    {
        return $this->amountToDistribute;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getLastModifiedAt(): DateTimeInterface
    {
        return $this->lastModifiedAt;
    }

    public function getDistributedAt(): DateTimeInterface | null
    {
        return $this->distributedAt;
    }
}
