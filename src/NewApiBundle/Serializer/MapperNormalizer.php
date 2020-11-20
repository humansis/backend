<?php
declare(strict_types=1);

namespace NewApiBundle\Serializer;

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
        if ($mapper = $this->getMapper($object)) {
            $mapper->populate($object);

            return parent::normalize($mapper, $format, $context);
        }

        throw new NotNormalizableValueException('Unable to normalize instance of '.get_class($object).'. No related Mapper found.');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        if (!is_object($data)) {
            return false;
        }

        foreach ($this->mappers as $mapper) {
            if ($mapper->supports($data)) {
                return true;
            }
        }

        return false;
    }

    private function getMapper($object)
    {
        foreach ($this->mappers as $mapper) {
            if ($mapper->supports($object)) {
                return $mapper;
            }
        }

        return null;
    }
}
