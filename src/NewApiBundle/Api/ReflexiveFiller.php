<?php
declare(strict_types=1);

namespace NewApiBundle\Api;

use Symfony\Component\PropertyAccess\PropertyAccess;

class ReflexiveFiller
{
    /** @var string[] */
    private $directMap = [];

    public function fillBy(object $filledObject, object $sourceObject): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $sourceReflection = new \ReflectionClass($sourceObject);

        foreach ($sourceReflection->getProperties() as $sourceProperty) {
            $sourcePropertyName = $sourceProperty->getName();
            $sourceValue = $propertyAccessor->getValue($sourceObject, $sourcePropertyName);

            if (array_key_exists($sourceProperty->getName(), $this->directMap)) {
                $targetPropertyName = $this->directMap[$sourceProperty->getName()];
            } else {
                $targetPropertyName = $sourceProperty->getName();
            }

            $propertyAccessor->setValue($filledObject, $targetPropertyName, $sourceValue);
        }
    }

    public function map(string $sourceProperty, string $targetProperty)
    {
        $this->directMap[$sourceProperty] = $targetProperty;
    }
}
