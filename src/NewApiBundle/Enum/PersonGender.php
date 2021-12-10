<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

class PersonGender
{
    use EnumTrait;

    const MALE = 'male';
    const FEMALE = 'female';

    protected static $values = [
        'Female' => self::FEMALE,
        'Male' => self::MALE,
    ];

    public static function values(): array
    {
        return array_values(self::$values);
    }

    protected static function apiAlternatives(): array
    {
        return [
            self::MALE => [1, 'm', 'man', 'men', 'true'],
            self::FEMALE => [0, 'f', 'fem', 'false', 'woman'],
        ];
    }

}
