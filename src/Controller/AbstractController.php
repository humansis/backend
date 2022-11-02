<?php

declare(strict_types=1);

namespace Controller;

use Serializer\MapperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class AbstractController extends SymfonyAbstractController
{
    /**
     * {@inheritdoc}
     */
    protected function json($data, $status = 200, $headers = [], $context = []): JsonResponse
    {
        if (!isset($context[MapperInterface::NEW_API])) {
            $context[MapperInterface::NEW_API] = true;
        }

        return parent::json($data, $status, $headers, $context);
    }

    protected function getCountryCode(Request $request): string
    {
        $countryIso3 = $request->headers->get('country');
        if (is_null($countryIso3)) {
            throw new BadRequestHttpException('Missing country header');
        }

        return $countryIso3;
    }
}
