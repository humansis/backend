<?php declare(strict_types=1);

namespace NewApiBundle\Component\SelectionCriteria\Enum;

use NewApiBundle\Enum\EnumTrait;

class CriteriaValueTransformerEnum
{
    use EnumTrait;

    public const CONVERT_TO_STRING = 'to_string';
    public const CONVERT_TO_INT = 'to_int';
    public const CONVERT_TO_FLOAT = 'to_float';
    public const CONVERT_TO_BOOL = 'to_bool';

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
