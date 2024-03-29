<?php

declare(strict_types=1);

namespace Component\SelectionCriteria\Enum;

use Enum\EnumTrait;

class CriteriaValueTransformerEnum
{
    use EnumTrait;

    final public const CONVERT_TO_STRING = 'to_string';
    final public const CONVERT_TO_INT = 'to_int';
    final public const CONVERT_TO_FLOAT = 'to_float';
    final public const CONVERT_TO_BOOL = 'to_bool';

    public static function values(): array
    {
        return [
            self::CONVERT_TO_STRING,
            self::CONVERT_TO_INT,
            self::CONVERT_TO_FLOAT,
            self::CONVERT_TO_BOOL,
        ];
    }
}
