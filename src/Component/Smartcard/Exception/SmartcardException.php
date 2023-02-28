<?php

declare(strict_types=1);

namespace Component\Smartcard\Exception;

use Exception;
use Throwable;
use Entity\Smartcard;

class SmartcardException extends Exception
{
    public function __construct(private readonly ?\Entity\Smartcard $smartcard = null, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return Smartcard|null
     */
    public function getSmartcard(): ?Smartcard
    {
        return $this->smartcard;
    }
}
