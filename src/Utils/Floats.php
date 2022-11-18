<?php

declare(strict_types=1);

namespace Utils;

final class Floats
{
    private const EPSILON = 0.0001;

    /**
     * Compares two floats. The comparison is done only approximately! because of hardware implementation of float type.
     * See warning about floating point precision here for more details: https://www.php.net/manual/en/language.types.float.php
     *
     *
     */
    public static function equals(float $a, float $b, float $epsilon = self::EPSILON): bool
    {
        return abs($a - $b) < $epsilon;
    }
}
