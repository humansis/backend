<?php
namespace CommonBundle\InputType;

use CommonBundle\Exception\BadRequestDataException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
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
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        $errors = [];
        if ($configuration->getClass() === Country::class) {
            if (!$request->request->has('__country')) {
                throw new \InvalidArgumentException("Missing __country in request body.");
            }
            $country = new Country($request->request->get('__country'));
            $errors = $this->validator->validate($country);
            $request->attributes->set($configuration->getName(), $country);
        } else {
            $requestData = $request->request->all();
            unset($requestData['__country']);

            $inputType = self::normalizeInputType($requestData, $configuration->getClass());
            $errors = $this->validator->validate($inputType);
            $request->attributes->set($configuration->getName(), $inputType);
        }
        if (count($errors) > 0) {
            $messages = [];
            /** @var ConstraintViolationInterface $error */
            foreach ($errors as $error) {
                $messages[] = $error->getMessage()." [{$error->getPropertyPath()} = {$error->getInvalidValue()}]";
            }
            throw new BadRequestDataException("Bad request body: ".implode(' | ', $messages));
        }
    }

    public static function normalizeInputType($data, $class): object
    {
        $serializer = new Serializer([new ObjectNormalizer(null, null, null, new ReflectionExtractor())]);
        return $serializer->denormalize($data, $class);
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getClass() !== null && in_array(InputTypeInterface::class, class_implements($configuration->getClass()));
    }
}
