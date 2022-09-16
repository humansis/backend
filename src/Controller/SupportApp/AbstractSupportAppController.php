<?php

namespace Controller\SupportApp;

use Controller\AbstractController;
use Serializer\MapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AbstractSupportAppController extends AbstractController
{
    /**
     * @param       $data
     * @param int   $status
     * @param array $headers
     * @param array $context
     *
     * @return JsonResponse
     */
    protected function json($data, $status = Response::HTTP_OK, $headers = [], $context = []): JsonResponse
    {
        if (!isset($context[MapperInterface::SUPPORT_APP])) {
            $context[MapperInterface::SUPPORT_APP] = true;
        }

        return parent::json($data, $status, $headers, $context);
    }
}
