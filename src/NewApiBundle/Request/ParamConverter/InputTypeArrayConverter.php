<?php

declare(strict_types=1);

namespace NewApiBundle\Request\ParamConverter;

use NewApiBundle\Exception\ConstraintViolationException;
use NewApiBundle\Request\InputTypeInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InputTypeArrayConverter implements ParamConverterInterface
{
    /** @var ValidatorInterface */
    private $validator;

    /**
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $serializer = new Serializer([new ObjectNormalizer(null, null, null, new ReflectionExtractor()), new ArrayDenormalizer()]);
        $inputType = $serializer->denormalize($request->request->all(), $configuration->getClass().'[]', null, [
            ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
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
    public function supports(ParamConverter $configuration)
    {
        return null !== $configuration->getClass() && in_array(InputTypeInterface::class, class_implements($configuration->getClass()));
    }
}
