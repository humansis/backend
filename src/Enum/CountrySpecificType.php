<?php

declare(strict_types=1);

namespace Enum;

final class CountrySpecificType
{
    use EnumTrait;

    public const NUMBER = 'number';
    public const TEXT = 'text';

    public static function values(): array
    {
        return [
            self::NUMBER,
            self::TEXT,
        ];
    }
}
