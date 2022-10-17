<?php

declare(strict_types=1);

namespace Controller;

use Serializer\MapperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class AbstractController extends Controller
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

    /**
     * @param Request $request
     *
     * @return string
     */
    protected function getCountryCode(Request $request): string
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        return $countryIso3;
    }
}
