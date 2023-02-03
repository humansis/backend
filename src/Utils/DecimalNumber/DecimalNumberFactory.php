<?php

declare(strict_types=1);

namespace Utils\DecimalNumber;

use PrestaShop\Decimal\DecimalNumber;

class DecimalNumberFactory
{
    public static function create(string | int | float $value, int $decimals = 2): DecimalNumber
    {
        return new DecimalNumber(
            match (true) {
                is_float($value) => number_format($value, $decimals, '.', ''),
                is_int($value) => strval($value),
                default => $value,
            }
        );
    }
}
