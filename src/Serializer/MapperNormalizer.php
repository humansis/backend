<?php

declare(strict_types=1);

namespace Serializer;

use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class MapperNormalizer extends ObjectNormalizer
{
    /** @var MapperInterface[] */
    private $mappers = [];

    /**
     * @param MapperInterface $mapper
     */
    public function registerMapper(MapperInterface $mapper)
    {
        $this->mappers[] = $mapper;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if ($mapper = $this->getMapper($object, $format, $context)) {
            $mapper->populate($object);

            return parent::normalize($mapper, $format, $context);
        }

        throw new NotNormalizableValueException('Unable to normalize instance of ' . get_class($object) . '. No related Mapper found.');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        if (!is_object($data)) {
            return false;
        }

        $context = null;
        if (func_num_args() > 2) {
            $context = func_get_arg(2);
        }

        foreach ($this->mappers as $mapper) {
            if ($mapper->supports($data, $format, $context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return false;
    }

    private function getMapper($object, $format = null, array $context = [])
    {
        foreach ($this->mappers as $mapper) {
            if ($mapper->supports($object, $format, $context)) {
                return $mapper;
            }
        }

        return null;
    }
}
