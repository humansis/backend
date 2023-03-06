<?php

declare(strict_types=1);

namespace Request\ParamConverter;

use Request\FormatInputType\FormatInputTypeInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class FormatConverter implements ParamConverterInterface
{
    public function apply(Request $request, ParamConverter $configuration)
    {
        $type = null;

        if ($request->query->has('type')) {
            $type = $request->query->all()['type'];
        }

        if ($type == null) {
            throw new BadRequestHttpException('Query parameter "type" must be existing.');
        }
        $classname = $configuration->getClass();

        $object = new $classname();
        $object->setType($type);

        $request->attributes->set($configuration->getName(), $object);

        return true;
    }

    public function supports(ParamConverter $configuration)
    {
        if (null === $configuration->getClass()) {
            return false;
        }

        $implements = class_implements($configuration->getClass());

        return in_array(FormatInputTypeInterface::class, $implements);
    }
}
