<?php

declare(strict_types=1);

namespace Enum;

trait EnumValueTrait
{
    public static function normalizeValue($value): string
    {
        if (is_string($value)) {
            $lowered = mb_strtolower($value);

            //removes every character which is not a number or a letter
            return preg_replace('|[\W_]+|', '', $lowered);
        }

        if (is_bool($value)) {
            return $value === true ? 'true' : 'false';
        }

        return (string) $value;
    }
}
