<?php

namespace DBAL;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;

abstract class AbstractEnum extends Type
{
    abstract public static function all();

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        $values = array_map(function ($val) {
            return "'" . $val . "'";
        }, $this::all());

        return "ENUM(" . implode(", ", $values) . ")";
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (null !== $value && !in_array($value, $this::all())) {
            $values = implode(', ', $this::all());
            throw new InvalidArgumentException("Invalid '" . $this->getName() . "' value. Value '$value' is not in [$values]");
        }

        return $value;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
