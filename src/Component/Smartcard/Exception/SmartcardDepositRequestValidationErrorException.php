<?php

declare(strict_types=1);

namespace Component\Smartcard\Exception;

use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class SmartcardDepositRequestValidationErrorException extends UnrecoverableMessageHandlingException
{
}
