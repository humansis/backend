<?php declare(strict_types=1);

namespace NewApiBundle\Utils\ValueGenerator;

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
        return $data[self::int(0, count($data) - 1)];
    }

    public static function fromEnum(string $enumClass)
    {
        if (!method_exists($enumClass, 'values') || !method_exists($enumClass, 'valueFromAPI')) {
            throw new \LogicException("$enumClass::values() or $enumClass::valueFromAPI() method is not defined.");
        }

        $value = self::fromArray($enumClass::values());

        return $enumClass::valueFromAPI($value);
    }

    public static function date(int $minAge, int $maxAge): \DateTime
    {
        $date = new \DateTime();
        $date->modify('-'.self::int($minAge, $maxAge).' years');

        return $date;
    }
}
