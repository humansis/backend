<?php

declare(strict_types=1);

namespace Utils\ValueGenerator;

use Enum\EnumTrait;
use LogicException;
use ReflectionException;
use Utils\Objects\Reflection;

class ValueGenerator
{
    public static function string(int $length): string
    {
        $bytes = random_bytes((int) ceil($length / 2));
        $string = bin2hex($bytes);

        return substr($string, 0, $length);
    }

    public static function int(int $min, int $max): int
    {
        return rand($min, $max);
    }

    public static function bool(): bool
    {
        return (bool) self::int(0, 1);
    }

    public static function fromArray(array $data)
    {
        $cnt = count($data);
        if ($cnt === 0) {
            throw new LogicException('Data array has no fields');
        }

        $keys = array_keys($data);
        if ($keys === range(0, $cnt - 1)) {
            return $data[self::int(0, $cnt - 1)];
        } else {
            return $data[$keys[self::int(0, count($keys) - 1)]];
        }
    }

    /**
     * @param string $enumClass
     *
     * @return mixed
     * @throws ReflectionException
     */
    public static function fromEnum(string $enumClass)
    {
        if (!Reflection::hasTrait($enumClass, EnumTrait::class)) {
            throw new LogicException(sprintf('%s must implement %s trait.', $enumClass, EnumTrait::class));
        }

        $value = self::fromArray($enumClass::values());

        return $enumClass::valueFromAPI($value);
    }
}
