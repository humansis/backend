<?php

declare(strict_types=1);

namespace Component\Smartcard\Exception;

use Exception;
use Throwable;
use Entity\SmartcardBeneficiary;

class SmartcardException extends Exception
{
    public function __construct(private readonly ?\Entity\SmartcardBeneficiary $smartcard = null, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return SmartcardBeneficiary|null
     */
    public function getSmartcard(): ?SmartcardBeneficiary
    {
        return $this->smartcard;
    }
}
