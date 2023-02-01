<?php

declare(strict_types=1);

namespace Component\Smartcard\Deposit;

class CreationContext
{
    public function __construct(
        private readonly bool $checkDistributionWorkflow = true,
        private readonly string | null $spent = null,
        private readonly string $notes = '',
    ) {
    }

    public function checkDistributionWorkflow(): bool
    {
        return $this->checkDistributionWorkflow;
    }

    public function getSpent(): ?string
    {
        return $this->spent;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }
}
