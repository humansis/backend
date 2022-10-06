<?php

namespace Exception;

use Exception;
use Throwable;

class ScoringCsvException extends Exception
{
    public function __construct(string $condition, string $message = '', int $code = 0, Throwable $previous = null)
    {
        $message = $message . ' (For condition ' . $condition . ')';

        parent::__construct($message, $code, $previous);
    }
}
