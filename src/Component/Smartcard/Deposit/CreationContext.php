<?php

declare(strict_types=1);

namespace Component\Smartcard\Deposit;

class CreationContext
{
    public function __construct(
        private bool $distributeAll = false,
        private bool $checkDistributionWorkflow = true,
    ) {
    }

    public function distributeAll(): bool
    {
        return $this->distributeAll;
    }

    public function checkDistributionWorkflow(): bool
    {
        return $this->checkDistributionWorkflow;
    }
}
