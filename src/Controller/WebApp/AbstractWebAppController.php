<?php

declare(strict_types=1);

namespace Controller\WebApp;

use Controller\AbstractController;
use Serializer\MapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AbstractWebAppController extends AbstractController
{
    /**
     * @param       $data
     * @param int $status
     * @param array $headers
     * @param array $context
     */
    protected function json($data, $status = Response::HTTP_OK, $headers = [], $context = []): JsonResponse
    {
        if (!isset($context[MapperInterface::WEB_API])) {
            $context[MapperInterface::WEB_API] = true;
        }

        return parent::json($data, $status, $headers, $context);
    }
}
