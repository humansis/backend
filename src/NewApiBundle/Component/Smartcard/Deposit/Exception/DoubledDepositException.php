<?php declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard\Deposit\Exception;

use VoucherBundle\Entity\SmartcardDeposit;

class DoubledDepositException extends DepositException
{
    public function __construct(SmartcardDeposit $deposit, ?string $message = null)
    {
        if(!$message){
            $message = "Deposit #{$deposit->getId()} already exists";
        }
        parent::__construct($deposit, $message);
    }
}
