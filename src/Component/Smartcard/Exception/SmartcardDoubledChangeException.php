<?php

declare(strict_types=1);

namespace Component\Smartcard\Exception;

use DateTimeInterface;
use Throwable;
use Entity\SmartcardBeneficiary;

class SmartcardDoubledChangeException extends SmartcardException
{
    public function __construct(?SmartcardBeneficiary $smartcard = null, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($smartcard, $message, $code, $previous);
        if (empty($message)) {
            $this->message = "Smartcard #{$smartcard->getId()} was already changed at {$smartcard->getChangedAt()->format(DateTimeInterface::ATOM)}";
        }
    }
}
