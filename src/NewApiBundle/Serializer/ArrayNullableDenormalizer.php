<?php declare(strict_types=1);

namespace NewApiBundle\Serializer;

use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;

class ArrayNullableDenormalizer extends ArrayDenormalizer
{
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        if (is_null($data)) {
            return [];
        }

        return parent::denormalize($data, $type, $format, $context);
    }

}
