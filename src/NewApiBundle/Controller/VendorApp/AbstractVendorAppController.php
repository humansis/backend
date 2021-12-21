<?php
declare(strict_types=1);

namespace NewApiBundle\Controller\VendorApp;

use NewApiBundle\Serializer\MapperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AbstractVendorAppController extends Controller
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
        if (!isset($context[MapperInterface::VENDOR_APP])) {
            $context[MapperInterface::VENDOR_APP] = true;
        }

        return parent::json($data, $status, $headers, $context);
    }
}
