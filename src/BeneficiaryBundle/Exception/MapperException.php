<?php

declare(strict_types=1);

namespace BeneficiaryBundle\Exception;

use Exception;

class MapperException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
