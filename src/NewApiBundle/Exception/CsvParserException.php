<?php

namespace NewApiBundle\Exception;

use Exception;
use Throwable;

class CsvParserException extends Exception
{
    public function __construct(string $file, $message = "", $code = 0, Throwable $previous = null)
    {
        $message .= " (in file $file)";

        parent::__construct($message, $code, $previous);
    }
}
