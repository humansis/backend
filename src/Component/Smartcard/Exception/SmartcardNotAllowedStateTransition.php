<?php

declare(strict_types=1);

namespace Component\Smartcard\Exception;

use Throwable;
use Entity\SmartcardBeneficiary;

/**
 * @deprecated Remove after implement symfony/workflow
 */
class SmartcardNotAllowedStateTransition extends SmartcardException
{
    public function __construct(
        SmartcardBeneficiary $smartcard,
        private readonly string $newState,
        $message = "",
        $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($smartcard, $message, $code, $previous);
    }

    public function getNewState(): string
    {
        return $this->newState;
    }
}
