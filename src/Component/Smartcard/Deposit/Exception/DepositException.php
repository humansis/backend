<?php

declare(strict_types=1);

namespace Component\Smartcard\Deposit\Exception;

use Exception;
use Entity\SmartcardDeposit;

class DepositException extends Exception
{
    public function __construct(private readonly ?SmartcardDeposit $deposit = null, string $message = '')
    {
        parent::__construct($message);
    }

    public function getDeposit(): ?SmartcardDeposit
    {
        return $this->deposit;
    }
}
