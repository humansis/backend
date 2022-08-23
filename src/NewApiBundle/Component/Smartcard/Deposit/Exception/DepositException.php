<?php declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard\Deposit\Exception;

use Exception;
use VoucherBundle\Entity\SmartcardDeposit;

class DepositException extends Exception
{
    /**
     * @var SmartcardDeposit
     */
    private $deposit;

    public function __construct(SmartcardDeposit $deposit, string $message = '')
    {
        $this->deposit = $deposit;
        parent::__construct($message);
    }

    /**
     * @return SmartcardDeposit
     */
    public function getDeposit(): SmartcardDeposit
    {
        return $this->deposit;
    }
}
