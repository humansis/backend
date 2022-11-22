<?php

namespace Exception;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;

class BadRequestDataException extends InvalidArgumentException implements RequestExceptionInterface
{
}
