<?php declare(strict_types=1);

namespace Component\Smartcard\Exception;

use Throwable;
use Entity\Smartcard;

class SmartcardDoubledRegistrationException extends SmartcardException
{
    public function __construct(Smartcard $smartcard, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($smartcard, $message, $code, $previous);
        if (empty($message)) {
            $this->message = "Smartcard #{$smartcard->getId()} was already registered at {$smartcard->getRegisteredAt()->format(\DateTimeInterface::ATOM)}";
        }
    }
}
