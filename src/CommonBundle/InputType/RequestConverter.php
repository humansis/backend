<?php

namespace CommonBundle\InputType;

use NewApiBundle\Exception\BadRequestDataException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestConverter implements ParamConverterInterface
{
    /** @var ValidatorInterface */
    private $validator;

    /**
     * RequestConverter constructor.
     *
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        $errors = [];
        if (Country::class === $configuration->getClass()) {
            if (!$request->request->has(Country::REQUEST_KEY)) {
                throw new \InvalidArgumentException('Missing '.Country::REQUEST_KEY.' in request body.');
            }
            $country = new Country($request->request->get(Country::REQUEST_KEY));
            $errors = $this->validator->validate($country);
            $request->attributes->set($configuration->getName(), $country);
        } else {
            $requestData = $request->request->all();
            unset($requestData[Country::REQUEST_KEY]);

            $inputType = self::normalizeInputType($requestData, $configuration->getClass());
            $errors = $this->validator->validate($inputType);
            $request->attributes->set($configuration->getName(), $inputType);
        }
        if (count($errors) > 0) {
            $messages = [];
            /** @var ConstraintViolationInterface $error */
            foreach ($errors as $error) {
                $value = $this->toString($error->getInvalidValue());
                $messages[] = $error->getMessage()." {$error->getPropertyPath()} = $value";
            }
            throw new BadRequestDataException('Bad request body: '.implode(' | ', $messages));
        }
        return true;
    }

    private function toString($value): string
    {
        if (null == $value) {
            return 'null';
        }
        if (is_object($value)) {
            return $value->__toString();
        }
        if (is_array($value)) {
            $values = array_map(function ($subvalue) { return $this->toString($subvalue); }, $value);

            return '['.implode(', ', $values).']';
        }

        return $value;
    }

    public static function normalizeInputType($data, $class): object
    {
        $serializer = new Serializer([new ObjectNormalizer(null, null, null, new ReflectionExtractor())]);

        return $serializer->denormalize($data, $class);
    }

    public function supports(ParamConverter $configuration)
    {
        return null !== $configuration->getClass() && in_array(InputTypeInterface::class, class_implements($configuration->getClass()));
    }
}
