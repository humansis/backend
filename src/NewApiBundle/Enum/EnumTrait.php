<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

trait EnumTrait
{
    public static abstract function values(): array;
    public static function apiAlternatives(): array {
        return [];
    }

    /**
     * @param int|string $APIValue
     *
     * @return string
     */
    public static function valueFromAPI($APIValue): string
    {
        foreach (self::values() as $originalValue) {
            if (self::normalizeValue($originalValue) == self::normalizeValue($APIValue)) {
                return $originalValue;
            }
        }
        foreach (self::apiAlternatives() ?? [] as $originalValue => $alternativeValues) {
            foreach ($alternativeValues as $alternativeValue) {
                if (self::normalizeValue($alternativeValue) == self::normalizeValue($APIValue)) {
                    return $originalValue;
                }
            }
        }
        throw new \InvalidArgumentException(
            sprintf("Enum type %s got value %s. Expected anything from '%s'.",
                __CLASS__,
                $APIValue,
                implode("', '", self::values())
            )
        );
    }

    /**
     * @param string $value
     *
     * @return string|int
     */
    public static function valueToAPI(string $value): string
    {
        return $value;
    }

    /**
     * @param string $value
     *
     * @return string|int
     */
    public static function valueToDB(string $value): string
    {
        return $value;
    }

    private static function normalizeValue(string $value): string
    {
        return preg_replace('|[^a-z]|', '', strtolower(trim($value)));
    }
}
