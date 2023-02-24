<?php

declare(strict_types=1);

namespace Request\ParamConverter;

use Exception\ConstraintViolationException;
use Request\InputTypeInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InputTypeConverter implements ParamConverterInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $inputType = $this->serializer->denormalize($request->request->all(), $configuration->getClass(), null, [
            AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
        ]);

        $errors = $this->validator->validate($inputType);
        if (count($errors) > 0) {
            throw new ConstraintViolationException($errors);
        }

        $request->attributes->set($configuration->getName(), $inputType);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ParamConverter $configuration): bool
    {
        $class = $this->getClassFromConfiguration($configuration);
        if (!$class) {
            return false;
        }

        return in_array(InputTypeInterface::class, class_implements($class));
    }

    protected function getClassFromConfiguration(ParamConverter $configuration): ?string
    {
        $class = $configuration->getClass();
        if (null === $class) {
            return null;
        }
        if (str_ends_with($class, '[]')) { // for support arrays of InputTypes
            $class = str_replace('[]', '', $class);
        }

        return $class;
    }
}
