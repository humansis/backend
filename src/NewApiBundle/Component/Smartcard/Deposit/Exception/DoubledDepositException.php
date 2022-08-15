<?php declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard\Deposit\Exception;

use NewApiBundle\Entity\SmartcardDeposit;

class DoubledDepositException extends DepositException
{
    public function __construct(SmartcardDeposit $deposit, ?string $message = null)
    {
        if (!$message) {
            $message = "Can't create Deposit with hash {$deposit->getHash()}. This hash is already used for Deposit #{$deposit->getId()}";
        }
        parent::__construct($deposit, $message);
    }
}
