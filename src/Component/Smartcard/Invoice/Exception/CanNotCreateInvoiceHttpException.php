<?php

declare(strict_types=1);

namespace Component\Smartcard\Invoice\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CanNotCreateInvoiceHttpException extends BadRequestHttpException
{
}
