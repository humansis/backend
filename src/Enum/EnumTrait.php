<?php
declare(strict_types=1);

namespace Enum;

trait EnumTrait
{
    public static abstract function values(): array;

    /**
     * @return string[][] key = value, value = array of possible values from API
     */
    public static function apiAlternatives(): array {
        return [];
    }

    /**
     * @return string[] key = value, value to return to API
     */
    protected static function apiMap(): array {
        if (!isset(self::$values)) {
            $values = [];
            foreach (self::values() as $value) {
                $values[$value] = $value;
            }
            return $values;
        }
        return array_flip(self::$values);
    }

    /**
     * @param int|string $APIValue
     *
     * @return string|int|bool
     * @throws EnumValueNoFoundException
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
        throw new EnumValueNoFoundException(__CLASS__, (string) $APIValue);
    }

    /**
     * @param string|int|bool $value
     *
     * @return string|int|bool
     *
     * @throws EnumApiValueNoFoundException
     */
    public static function valueToAPI($value)
    {
        if (!isset(self::apiMap()[$value]))
            throw new EnumApiValueNoFoundException(__CLASS__, $value);
        return self::apiMap()[$value];
    }

    /**
     * Everytime this function is updated, table location needs to be migrated. enum_normalized_name column needs to be generated again.
     *
     * @param $value
     *
     * @return string
     */
    public static function normalizeValue($value): string
    {
        if (is_string($value)) {
            $lowered = mb_strtolower($value);

            //removes every character which is not a number or a letter
            return preg_replace('|[\W_]+|', '', $lowered);
        }
        if (is_bool($value)) return $value === true ? 'true' : 'false';
        return (string) $value;
    }
}