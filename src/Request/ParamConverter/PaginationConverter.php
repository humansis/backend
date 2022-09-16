<?php

declare(strict_types=1);

namespace Request\ParamConverter;

use Request\Pagination;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PaginationConverter implements ParamConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $page = $request->query->getInt('page', 1);
        $size = $request->query->getInt('size', Pagination::DEFAULT_SIZE);

        if ($page < 1) {
            throw new BadRequestHttpException('Query parameter page must be greater than zero.');
        }

        if ($size < 1) {
            throw new BadRequestHttpException('Query parameter size must be greater than zero.');
        }

        $request->attributes->set($configuration->getName(), new Pagination($page, $size));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ParamConverter $configuration)
    {
        return Pagination::class === $configuration->getClass();
    }
}
