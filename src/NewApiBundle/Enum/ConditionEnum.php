<?php

namespace NewApiBundle\Enum;

use NewApiBundle\Component\Codelist\CodeItem;

class ConditionEnum
{
    use EnumTrait;

    const EQ = '=';
    const LT = '<';
    const GT = '>';
    const LTE = '<=';
    const GTE = '>=';

    public static $values = [
        self::EQ => 'Equals',
        self::LT => 'Less then',
        self::GT => 'Greater then',
        self::LTE => 'Less then or equals',
        self::GTE => 'Greater then or equals',
    ];

    public static function values(): array
    {
        return [
            self::EQ,
            self::LT,
            self::GT,
            self::LTE,
            self::GTE,
        ];
    }

    /**
     * @param array $keys
     *
     * @return CodeItem[]
     */
    public static function createCodeItems(array $keys): array
    {
        return array_map(function ($key) {
            return new CodeItem($key, self::$values[$key]);
        }, $keys);
    }

}
