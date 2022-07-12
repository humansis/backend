<?php
declare(strict_types=1);

namespace NewApiBundle\Utils\Objects;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;

trait PropertySetter
{

    /**
     * Set values to properties according to array
     * If property does not exist just silently continue
     *
     * @param iterable $properties
     *
     * @return void
     */
    public function setValues(iterable $properties) {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($properties as $property => $value) {
            if ($propertyAccessor->isWritable($this, $property)) {
                $propertyAccessor->setValue($this, $property, $value);
            }
        }
    }

}