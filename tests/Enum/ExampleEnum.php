<?php

declare(strict_types=1);

namespace Tests\Enum;

use Enum\EnumTrait;

class ExampleEnum
{
    use EnumTrait;

    final public const HORSE = 'příliš žluťoučký kůň pěl ďábelské ódy';
    final public const AAA = 'AAA';
    final public const OBFUSCATE = 'A - B - C/D_E';
    final public const YES = true;

    public static function values(): array
    {
        return [
            self::HORSE,
            self::AAA,
            self::OBFUSCATE,
            self::YES,
        ];
    }

    public static function apiAlternatives(): array
    {
        return [
            self::HORSE => ['kun', 1],
            self::AAA => ['a', 3, '3a'],
            self::OBFUSCATE => ['obf', 0, false],
            self::YES => ['YES', 1024],
        ];
    }

    protected static function apiMap(): array
    {
        return [
            self::HORSE => 1,
            self::AAA => 3,
            self::OBFUSCATE => 0,
            self::YES => 1024,
        ];
    }
}
