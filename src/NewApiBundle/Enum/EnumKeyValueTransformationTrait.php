<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

trait EnumKeyValueTransformationTrait
{
    public static function all(): array
    {
        return self::$values;
    }

    public static function keys(): array
    {
        return array_keys(self::$values);
    }

    public static function getByKey(string $key): ?string
    {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }
        return null;
    }

    public static function getByKeys(array $keys): array
    {
        $values = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, self::$values)) {
                $values[] = self::$values[$key];
            }
        }
        return $values;
    }

    public static function getKey(string $value): ?int
    {
        foreach (self::$values as $key => $originalValue) {
            if (strtolower($originalValue) == strtolower(trim($value))) {
                return $key;
            }
        }
        return null;
    }

    public static function getKeys(array $values): array
    {
        $keys = [];
        $nonexistent = [];
        foreach ($values as $value) {
            $key = self::getKey($value);
            if (null !== $key) {
                $keys[] = $key;
            } else {
                $nonexistent[] = $key;
            }
        }
        if (!empty($nonexistent)) {
            throw new \InvalidArgumentException("'".implode("', '", $nonexistent)."' are not valid assets.");
        }
        return $keys;
    }

    public static function hasValue(string $value): bool
    {
        foreach (self::$values as $key => $originalValue) {
            if (strtolower($originalValue) == strtolower(trim($value))) {
                return true;
            }
        }
        return false;
    }

    public static function hasKey(string $key): bool
    {
        return array_key_exists($key, self::$values);
    }
}
