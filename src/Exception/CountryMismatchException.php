<?php

namespace Exception;

use RuntimeException;
use Throwable;

class CountryMismatchException extends RuntimeException
{
    public function __construct($message = "Country codes differ", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
