<?php
declare(strict_types=1);

namespace NewApiBundle\Api;

use Symfony\Component\PropertyAccess\PropertyAccess;

class ReflexiveFiller
{
    const TARGET_PROPERTY = 0;
    const TRANSFORM_ALL = 1;
    const TRANSFORM_EACH = 2;

    /**
     * @var array string => callable|['property','transformation'=>callable,'foreach'=>callable]|null
     */
    private $propertyHooks = [];

    public function fill(object $filledObject, object $sourceObject): object
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $sourceReflection = new \ReflectionClass($sourceObject);

        foreach ($sourceReflection->getProperties() as $sourceProperty) {
            $sourcePropertyName = $sourceProperty->getName();
            $sourceValue = $propertyAccessor->getValue($sourceObject, $sourcePropertyName);

            // default way => map property to same name without changes
            if (!array_key_exists($sourcePropertyName, $this->propertyHooks)) {
                $propertyAccessor->setValue($filledObject, $sourcePropertyName, $sourceValue);
                continue;
            }

            $hook = $this->propertyHooks[$sourcePropertyName];

            // ignore property
            if (null === $hook) continue;

            if (is_callable($hook)) {
                $hook($sourceValue, $filledObject);
                continue;
            }

            if (array_key_exists(self::TARGET_PROPERTY, $hook)) {
                $targetPropertyName = $hook[self::TARGET_PROPERTY];
            } else {
                $targetPropertyName = $sourcePropertyName;
            }

            if (array_key_exists(self::TRANSFORM_ALL, $hook) && null !== $hook[self::TRANSFORM_ALL]) {
                $callback = $hook[self::TRANSFORM_ALL];
                $targetValue = $callback($sourceValue, $filledObject);
            } else {
                $targetValue = $sourceValue;
            }

            if (array_key_exists(self::TRANSFORM_EACH, $hook)
                && is_iterable($targetValue)
                && null !== $hook[self::TRANSFORM_EACH]
            ) {
                $callback = $hook[self::TRANSFORM_EACH];
                $newCollection = [];
                foreach ($targetValue as $key => $item) {
                    $newCollection[$key] = $callback($key, $item, $filledObject);
                }
                $targetValue = $newCollection;
            }

            $propertyAccessor->setValue($filledObject, $targetPropertyName, $targetValue);
        }

         return $filledObject;
    }

    public function map(string $sourceProperty, string $targetProperty, ?callable $transformation = null)
    {
        $this->propertyHooks[$sourceProperty] = [
            self::TARGET_PROPERTY => $targetProperty,
            self::TRANSFORM_ALL => $transformation,
        ];
    }

    public function mapEach(string $sourceProperty, string $targetProperty, callable $itemTransformation)
    {
        $this->propertyHooks[$sourceProperty] = [
            self::TARGET_PROPERTY => $targetProperty,
            self::TRANSFORM_EACH => $itemTransformation,
        ];
    }

    public function callback(string $sourceProperty, callable $callback)
    {
        $this->propertyHooks[$sourceProperty] = $callback;
    }

    public function foreach(string $sourceProperty, callable $callback)
    {
        $this->propertyHooks[$sourceProperty] = [
            self::TRANSFORM_EACH => $callback,
        ];
    }

    public function transform(string $sourceProperty, callable $callback)
    {
        $this->propertyHooks[$sourceProperty] = [
            self::TRANSFORM_ALL => $callback,
        ];
    }

    public function ignore($propertiesToIgnore): void
    {
        if (is_array($propertiesToIgnore)) {
            foreach ($propertiesToIgnore as $property) {
                $this->propertyHooks[$property] = null;
            }
        } else {
            $this->propertyHooks[$propertiesToIgnore] = null;
        }
    }
}
