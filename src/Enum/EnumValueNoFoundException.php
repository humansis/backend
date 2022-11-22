<?php

declare(strict_types=1);

namespace Enum;

use Exception;
use Throwable;

class EnumValueNoFoundException extends Exception
{
    public function __construct(string $enumClassName, string $searchedValue)
    {
        parent::__construct(
            sprintf(
                "Enum type %s got api value %s. Expected values ['%s'] and similarities",
                $enumClassName,
                $searchedValue,
                implode("', '", $enumClassName::values())
            )
        );
    }
}
