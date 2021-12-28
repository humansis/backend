<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

class PersonGender
{
    use EnumTrait;

    const MALE = 'male';
    const FEMALE = 'female';

    protected static $values = [
        0 => self::FEMALE,
        1 => self::MALE,
    ];

    public static function values(): array
    {
        return array_values(self::$values);
    }

    protected static function apiAlternatives(): array
    {
        return [
            self::MALE => ['m', 'man', 'men', true, 'true'],
            self::FEMALE => ['f', 'fem', 'false', false],
        ];
    }

}
