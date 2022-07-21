<?php declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard\Exception;

use DateTimeInterface;
use Throwable;
use VoucherBundle\Entity\Smartcard;

class SmartcardDoubledChangeException extends SmartcardException
{
    public function __construct(?Smartcard $smartcard = null, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($smartcard, $message, $code, $previous);
        if (empty($message)) {
            $this->message = "Smartcard #{$smartcard->getId()} was already changed at {$smartcard->getChangedAt()->format(DateTimeInterface::ATOM)}";
        }
    }
}
