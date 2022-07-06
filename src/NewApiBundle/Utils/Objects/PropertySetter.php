<?php

namespace NewApiBundle\Utils\Objects;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;

trait PropertySetter
{

    /**
     * Set values to properties according to array
     * If property does not exist just silently continue
     * @param $properties
     *
     * @return void
     */
    public function setValues($properties) {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($properties as $property => $value) {
            if ($propertyAccessor->isWritable($this, $property)) {
                $propertyAccessor->setValue($this, $property, $value);
            }
        }
    }

}