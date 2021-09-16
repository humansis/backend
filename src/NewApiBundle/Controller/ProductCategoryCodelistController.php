<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use CommonBundle\Pagination\Paginator;
use NewApiBundle\Component\Codelist\CodeLists;
use NewApiBundle\Enum\ProductCategoryType;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductCategoryCodelistController extends AbstractController
{
    /**
     * @Rest\Get("/web-app/v1/product-categories/types")
     *
     * @return JsonResponse
     */
    public function getTypes(): JsonResponse
    {
        $data = CodeLists::mapEnum(ProductCategoryType::values());

        return $this->json(new Paginator($data));
    }
}
