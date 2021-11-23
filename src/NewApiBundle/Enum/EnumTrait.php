<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

trait EnumTrait
{
    public static abstract function values(): array;

    /**
     * @return string[][] key = value, value = array of possible values from API
     */
    protected static function apiAlternatives(): array {
        return [];
    }

    /**
     * @return string[] key = value, value to return to API
     */
    protected static function apiMap(): array {
        if (!isset(self::$values)) {
            return [];
        }
        return array_flip(self::$values);
    }

    /**
     * @param int|string $APIValue
     *
     * @return string|int|bool
     */
    public static function valueFromAPI($APIValue)
    {
        $normalizedApiValue = self::normalizeValue($APIValue);
        foreach (self::values() as $originalValue) {
            if (self::normalizeValue($originalValue) === $normalizedApiValue) {
                return $originalValue;
            }
        }
        foreach (self::apiMap() as $originalValue => $apiValue) {
            if (self::normalizeValue($apiValue) === $normalizedApiValue) {
                return $originalValue;
            }
        }
        foreach (self::apiAlternatives() as $originalValue => $alternativeValues) {
            foreach ($alternativeValues as $alternativeValue) {
                if (self::normalizeValue($alternativeValue) === $normalizedApiValue) {
                    return $originalValue;
                }
            }
        }
        echo "INPUT: $APIValue = $normalizedApiValue\n";
        foreach (self::values() as $value) {
            $normalizedValue = self::normalizeValue($value);
            echo "$value = $normalizedValue\n";
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
     * @param string|int|bool $value
     *
     * @return string|int|bool
     */
    public static function valueToAPI($value)
    {
        return self::apiMap()[$value];
    }

    private static function normalizeValue($value): string
    {
        if (is_string($value)) return preg_replace('|[\W_]+|', '', strtolower(trim($value)));
        if (is_bool($value)) return $value ? 'true' : 'false';
        return (string) $value;
    }
}
