<?php declare(strict_types=1);

namespace NewApiBundle\Component\SelectionCriteria\Enum;

use NewApiBundle\Enum\EnumTrait;

class CriteriaReturnFunctionEnum
{
    use EnumTrait;

    public const CONVERT_TO_STRING = 'to_string';
    public const CONVERT_TO_INT = 'to_int';

    public static function values(): array
    {
        return [
            self::CONVERT_TO_STRING,
            self::CONVERT_TO_INT,
        ];
    }

}
