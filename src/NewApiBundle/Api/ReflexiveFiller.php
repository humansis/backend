<?php
declare(strict_types=1);

namespace NewApiBundle\Api;

use Symfony\Component\PropertyAccess\PropertyAccess;

class ReflexiveFiller
{
    /** @var string[] */
    private $directMap = [];

    /** @var string[] */
    private $callbackMap = [];

    /** @var string[] */
    private $propertiesToIgnore = [];

    public function fillBy(object $filledObject, object $sourceObject): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $sourceReflection = new \ReflectionClass($sourceObject);

        foreach ($sourceReflection->getProperties() as $sourceProperty) {
            $sourcePropertyName = $sourceProperty->getName();
            if (in_array($sourcePropertyName, $this->propertiesToIgnore)) continue;

            echo $sourcePropertyName;
            $sourceValue = $propertyAccessor->getValue($sourceObject, $sourcePropertyName);

            if (array_key_exists($sourceProperty->getName(), $this->callbackMap)) {
                $callback = $this->callbackMap[$sourceProperty->getName()];
                $callback($sourceValue, $filledObject);
                continue;
            }

            if (array_key_exists($sourceProperty->getName(), $this->directMap)) {
                $targetPropertyName = $this->directMap[$sourceProperty->getName()];
            } else {
                $targetPropertyName = $sourceProperty->getName();
            }
            echo " => ".$targetPropertyName."\n";

            $propertyAccessor->setValue($filledObject, $targetPropertyName, $sourceValue);
        }
    }

    public function map(string $sourceProperty, string $targetProperty)
    {
        $this->directMap[$sourceProperty] = $targetProperty;
    }

    public function callback(string $sourceProperty, callable $callback)
    {
        $this->callbackMap[$sourceProperty] = $callback;
    }

    public function ignore($propertiesToIgnore): void
    {
        if (is_array($propertiesToIgnore)) {
            foreach ($propertiesToIgnore as $property) {
                $this->propertiesToIgnore[] = $property;
            }
        } else {
            $this->propertiesToIgnore[] = $propertiesToIgnore;
        }
    }
}
