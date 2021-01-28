<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use NewApiBundle\Serializer\MapperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;

abstract class AbstractController extends Controller
{
    /**
     * {@inheritdoc}
     */
    protected function json($data, $status = 200, $headers = [], $context = []): \Symfony\Component\HttpFoundation\JsonResponse
    {
        if (!isset($context[MapperInterface::NEW_API])) {
            $context[MapperInterface::NEW_API] = true;
        }

        return parent::json($data, $status, $headers, $context);
    }
}
