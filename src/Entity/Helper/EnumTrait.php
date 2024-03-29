<?php

declare(strict_types=1);

namespace Entity\Helper;

use InvalidArgumentException;

trait EnumTrait
{
    public static function validateValue(
        string $attributeName,
        string $enumClass,
        $value,
        bool $isNullValid = false
    ): void {
        if (!in_array(\Enum\EnumTrait::class, class_uses($enumClass))) {
            throw new InvalidArgumentException("Wrong enum class");
        }
        if (null === $value) {
            if ($isNullValid) {
                return;
            } else {
                throw new InvalidArgumentException("Argument can't be null");
            }
        }

        if (!$enumClass::valueFromAPI($value)) {
            throw new InvalidArgumentException(
                sprintf(
                    "Attribute %s got receive enum type key %s. Expected anything from '%s'.",
                    $attributeName,
                    $value,
                    implode("', '", $enumClass::values())
                )
            );
        }
    }

    /**
     * @param string[] $values
     */
    public static function validateValues(string $attributeName, string $enumClass, iterable $values): void
    {
        foreach ($values as $value) {
            self::validateValue($attributeName, $enumClass, $value, false);
        }
    }
}
