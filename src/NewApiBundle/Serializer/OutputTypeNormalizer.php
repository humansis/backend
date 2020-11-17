<?php
declare(strict_types=1);

namespace NewApiBundle\Serializer;

use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class OutputTypeNormalizer extends ObjectNormalizer
{
    /** @var OutputTypeInterface[] */
    private $outputTypes = [];

    /**
     * @param OutputTypeInterface $outputType
     */
    public function registerOutputType(OutputTypeInterface $outputType)
    {
        $this->outputTypes[] = $outputType;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if ($outputType = $this->getOutputType($object)) {
            $outputType->populate($object);

            return parent::normalize($outputType, $format, $context);
        }

        throw new NotNormalizableValueException('Unable to normalize instance of '.get_class($object).'. No related OutputType found.');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        if (!is_object($data)) {
            return false;
        }

        foreach ($this->outputTypes as $outputType) {
            if ($outputType->supports($data)) {
                return true;
            }
        }

        return false;
    }

    private function getOutputType($object)
    {
        foreach ($this->outputTypes as $outputType) {
            if ($outputType->supports($object)) {
                return $outputType;
            }
        }

        return null;
    }
}
