<?php declare(strict_types=1);

namespace NewApiBundle\Request\ParamConverter;

use NewApiBundle\Request\InputTypeNullableDenormalizer;
use NewApiBundle\Serializer\ArrayNullableDenormalizer;
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

    /**
     * @return ContextAwareDenormalizerInterface
     */
    protected function getArrayDenormalizer(): ContextAwareDenormalizerInterface
    {
        return new ArrayNullableDenormalizer();
    }
}
