<?php

declare(strict_types=1);

namespace Component\Smartcard\Exception;

use DateTimeInterface;
use Throwable;
use Entity\SmartcardBeneficiary;

class SmartcardDoubledChangeException extends SmartcardException
{
    public function __construct(?SmartcardBeneficiary $smartcardBeneficiary = null, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($smartcardBeneficiary, $message, $code, $previous);
        if (empty($message)) {
            $this->message = "Smartcard #{$smartcardBeneficiary->getId()} was already changed at {$smartcardBeneficiary->getChangedAt()->format(DateTimeInterface::ATOM)}";
        }
    }
}
