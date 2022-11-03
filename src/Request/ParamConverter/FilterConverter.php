<?php

declare(strict_types=1);

namespace Request\ParamConverter;

use Exception\ConstraintViolationException;
use Request\FilterInputType\FilterInputTypeInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FilterConverter implements ParamConverterInterface
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $filter = [];

        if ($request->query->has('sort')) {
            $filter = $request->query->get('filter');
        }

        if (!is_array($filter)) {
            throw new BadRequestHttpException('Query parameter filter must be an array.');
        }

        $classname = $configuration->getClass();

        $object = new $classname();
        $object->setFilter($filter);

        $errors = $this->validator->validate($object);
        if (($errors === null ? 0 : count($errors)) > 0) {
            throw new ConstraintViolationException($errors);
        }

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

        return in_array(FilterInputTypeInterface::class, $implements);
    }
}
