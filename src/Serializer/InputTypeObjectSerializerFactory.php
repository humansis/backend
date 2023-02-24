<?php

declare(strict_types=1);

namespace Serializer;

use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

final class InputTypeObjectSerializerFactory
{
    public static function createSerializer(): SerializerInterface
    {
        return new Serializer(
            [
                new DateTimeNormalizer(),
                new ObjectNormalizer(
                    propertyTypeExtractor: new ReflectionExtractor()
                ),
                new ArrayDenormalizer(),
            ]
        );
    }
}
