<?php

declare(strict_types=1);

namespace Utils\Objects;

use ReflectionClass;
use ReflectionException;

class Reflection
{
    /**
     * @param object|string $object
     *
     * @throws ReflectionException
     */
    public static function getReflectionClass($object): ReflectionClass
    {
        return new ReflectionClass($object);
    }

    /**
     * @param        $object
     *
     * @throws ReflectionException
     */
    public static function hasTrait($object, string $traitName): bool
    {
        return in_array($traitName, self::getReflectionClass($object)->getTraitNames());
    }
}
