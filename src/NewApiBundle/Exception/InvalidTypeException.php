<?php


namespace NewApiBundle\Exception;


use Throwable;

class InvalidTypeException extends \Exception
{
    public function __construct(string $expectedType, int $code = 0, Throwable $previous = null)
    {
        $message = "Expected type was '$expectedType'";

        parent::__construct($message, $code, $previous);
    }
}
