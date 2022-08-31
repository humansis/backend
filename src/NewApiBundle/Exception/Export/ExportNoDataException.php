<?php declare(strict_types=1);

namespace NewApiBundle\Exception\Export;


use Exception;
use Throwable;

class ExportNoDataException extends Exception
{
    public function __construct($message = "Export contains no data", $code = 204, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
