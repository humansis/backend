<?php
declare(strict_types=1);

namespace Utils\Objects;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;

trait PropertyList
{

    /**
     * Get all properties of object
     * @return array|string[]|null
     */
    public function getProperties()
    {
        $reflectionExtractor = new ReflectionExtractor();
        $properties = $reflectionExtractor->getProperties(static::class);
        return array_diff($properties, ['properties', 'filledValues']);
    }

    /**
     * Get all setted properties as array with values
     * @return array
     */
    public function getFilledValues() {
        $properties = $this->getProperties();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $values = [];
        foreach ($properties as $property) {
            $value = $propertyAccessor->getValue($this, $property);
            if (isset($value)) {
                $values[$property] = $value;
            }
        }
        return $values;
    }


}