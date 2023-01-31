<?php

declare(strict_types=1);

namespace Component\Smartcard\Deposit;

class CreationContext
{
    public function __construct(
        private bool $checkDistributionWorkflow = true,
        private float|null $spent = null,
        private string $notes = '',
    ) {
    }

    public function checkDistributionWorkflow(): bool
    {
        return $this->checkDistributionWorkflow;
    }

    public function getSpent(): ?float
    {
        return $this->spent;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }
}
