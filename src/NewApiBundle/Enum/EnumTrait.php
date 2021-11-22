<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

trait EnumTrait
{
    public static abstract function values(): array;
    protected static function apiAlternatives(): array {
        return [];
    }

    /**
     * @param int|string $APIValue
     *
     * @return string
     */
    public static function valueFromAPI($APIValue): string
    {
        $normalizedApiValue = self::normalizeValue($APIValue);
        foreach (self::values() as $originalValue) {
            if (self::normalizeValue($originalValue) == $normalizedApiValue) {
                return $originalValue;
            }
        }
        foreach (self::apiAlternatives() ?? [] as $originalValue => $alternativeValues) {
            foreach ($alternativeValues as $alternativeValue) {
                if (self::normalizeValue($alternativeValue) == $normalizedApiValue) {
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

    private static function normalizeValue($value): string
    {
        if (is_string($value)) return preg_replace('|\W+|', '', strtolower(trim($value)));
        return (string) $value;
    }
}
