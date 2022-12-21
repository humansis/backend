<?php

namespace Component\Import\Enum;

enum ImportCsoEnum: string
{
    case MappingKey = 'countrySpecifics';
    case CsoColumnPath = 'CountrySpecifics';
    case ImportLineEntityKey = 'countrySpecific';
    case ImportLineValueKey = 'value';

    public static function getCsoColumnMapping(string $column): string
    {
        return self::CsoColumnPath->value . '.' . $column;
    }
}
