<?php

declare(strict_types=1);

namespace Serializer;

use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ArrayNullableDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface, CacheableSupportsMethodInterface
{
    private ArrayDenormalizer $denormalizer;

    public function __construct()
    {
        $this->denormalizer = new ArrayDenormalizer();
    }

    public function denormalize($data, $type, $format = null, array $context = []): array
    {
        if (is_null($data)) {
            return [];
        }
        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return $this->denormalizer->hasCacheableSupportsMethod();
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $this->denormalizer->supportsDenormalization($data, $type, $format, $context);
    }

    public function setDenormalizer(DenormalizerInterface $denormalizer)
    {
        $this->denormalizer->setDenormalizer($denormalizer);
    }
}
