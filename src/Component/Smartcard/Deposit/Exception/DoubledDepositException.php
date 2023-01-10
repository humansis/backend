<?php

declare(strict_types=1);

namespace Component\Smartcard\Deposit\Exception;

class DoubledDepositException extends DepositException
{
    public function __construct(string $message)
    {
        parent::__construct(null, $message);
    }
}
