<?php declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp;

use NewApiBundle\Controller\AbstractController;
use NewApiBundle\Serializer\MapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AbstractOfflineAppController extends AbstractController
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
        if (!isset($context[MapperInterface::OFFLINE_APP])) {
            $context[MapperInterface::OFFLINE_APP] = true;
        }

        return parent::json($data, $status, $headers, $context);
    }
}
