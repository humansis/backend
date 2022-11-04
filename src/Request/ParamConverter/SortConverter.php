<?php

declare(strict_types=1);

namespace Request\ParamConverter;

use Request\OrderInputType\SortInputTypeInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SortConverter implements ParamConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $sort = [];

        if ($request->query->has('sort')) {
            $sort = $request->query->all()['sort'];
        }

        if (!is_array($sort)) {
            throw new BadRequestHttpException('Query parameter sort must be array.');
        }

        $classname = $configuration->getClass();

        $object = new $classname();
        $object->setOrderBy($sort);

        $request->attributes->set($configuration->getName(), $object);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ParamConverter $configuration)
    {
        if (null === $configuration->getClass()) {
            return false;
        }

        $implements = class_implements($configuration->getClass());

        return in_array(SortInputTypeInterface::class, $implements);
    }
}
