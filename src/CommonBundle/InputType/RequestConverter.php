<?php
namespace CommonBundle\InputType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class RequestConverter implements ParamConverterInterface
{

    public function apply(Request $request, ParamConverter $configuration)
    {
        if ($configuration->getClass() === Country::class) {
            if (!$request->request->has('__country')) {
                throw new \InvalidArgumentException("Missing __country in request body.");
            }
            $request->attributes->set($configuration->getName(), new Country($request->request->get('__country')));
        } else {
            $requestData = $request->request->all();
            unset($requestData['__country']);

            $serializer = new Serializer([new ObjectNormalizer()]);
            $inputType = $serializer->denormalize($requestData, $configuration->getClass());
            $request->attributes->set($configuration->getName(), $inputType);
        }
    }

    public function supports(ParamConverter $configuration)
    {
        return in_array(InputTypeInterface::class, class_implements($configuration->getClass()));
    }
}