<?php
declare(strict_types=1);

namespace DBAL;

trait EnumTrait
{
    /**
     * @return array dbValue => enumValue
     */
    public abstract static function databaseMap(): array;

    public static function valueFromDB($dbValue): ?string
    {
        if ($dbValue === null || $dbValue === '') {
            return null;
        }
        if (!array_key_exists($dbValue, self::databaseMap())) {
            throw new \Exception("Database value $dbValue cannot be mapped to application enum");
        }
        return self::databaseMap()[$dbValue];
    }

    public static function valueToDB($appValue)
    {
        if ($appValue === null || $appValue === '') {
            return null;
        }
        foreach (self::databaseMap() as $dbValue => $applicationValue) {
            if ($appValue === $applicationValue) return $dbValue;
        }
        throw new \Exception("Application enum $appValue cannot be mapped to database value");
    }
}
