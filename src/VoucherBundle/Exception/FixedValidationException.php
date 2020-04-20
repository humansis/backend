<?php

namespace VoucherBundle\Exception;

/**
 * Class FixedValidationException repairs error message produced by \RA\RequestValidatorBundle\RequestValidator\ValidationException.
 *
 * In \RA\RequestValidatorBundle\RequestValidator\ValidationException, information about invalid fields is lost due to wrong message processing.
 */
class FixedValidationException extends \Exception
{
    public function __construct(\RA\RequestValidatorBundle\RequestValidator\ValidationException $ex)
    {
        $errors = current($ex->getErrors());

        $messages = [];
        foreach ($errors as $key => $values) {
            $messages[] = $key . ': ' . implode(', ', (array) $values);
        }

        parent::__construct(implode("\n", $messages), $ex->getCode(), $ex->getPrevious());
    }
}
