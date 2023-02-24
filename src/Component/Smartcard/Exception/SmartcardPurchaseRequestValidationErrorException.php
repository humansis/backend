<?php

declare(strict_types=1);

namespace Component\Smartcard\Exception;

use Exception;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class SmartcardPurchaseRequestValidationErrorException extends UnrecoverableMessageHandlingException
{
}
