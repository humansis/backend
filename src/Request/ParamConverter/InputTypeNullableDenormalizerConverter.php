<?php

declare(strict_types=1);

namespace Request\ParamConverter;

use Request\InputTypeNullableDenormalizer;
use Serializer\ArrayNullableDenormalizer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;

class InputTypeNullableDenormalizerConverter extends InputTypeConverter
{
    /**
     * {@inheritdoc}
     */
    public function supports(ParamConverter $configuration): bool
    {
        $class = $this->getClassFromConfiguration($configuration);
        if (!$class) {
            return false;
        }

        return in_array(InputTypeNullableDenormalizer::class, class_implements($class));
    }

    protected function getArrayDenormalizer(): ContextAwareDenormalizerInterface
    {
        return new ArrayNullableDenormalizer();
    }
}
