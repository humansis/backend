<?php

declare(strict_types=1);

namespace Component\Smartcard\Deposit\Exception;

use Entity\SmartcardDeposit;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class DepositException extends UnrecoverableMessageHandlingException
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
