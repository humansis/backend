<?php
declare(strict_types=1);

namespace NewApiBundle\Api;

use Symfony\Component\PropertyAccess\PropertyAccess;

class ReflexiveFiller
{
    public function fillBy(object $filledObject, object $sourceObject): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $reflectionClass = new \ReflectionClass($sourceObject);

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $propertyName = $reflectionProperty->getName();
            $newValue = $propertyAccessor->getValue($sourceObject, $propertyName);

            $propertyAccessor->setValue($filledObject, $propertyName, $newValue);
        }
    }
}
