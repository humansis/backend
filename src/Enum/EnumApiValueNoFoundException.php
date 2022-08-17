<?php
declare(strict_types=1);

namespace Enum;

use Throwable;

class EnumApiValueNoFoundException extends \Exception
{
    public function __construct(string $enumClassName, string $searchedValue)
    {
        parent::__construct(sprintf("Enum type %s got value %s. Expected values ['%s'] and similarities",
            $enumClassName,
            $searchedValue,
            implode("', '", array_keys($enumClassName::apiMap()))
        ));
    }

}
