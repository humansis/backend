<?php

declare(strict_types=1);

namespace Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Pagination\Paginator;
use Enum\ProductCategoryType;
use Services\CodeListService;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductCategoryCodelistController extends AbstractController
{
    public function __construct(private readonly CodeListService $codeListService)
    {
    }

    /**
     * @Rest\Get("/web-app/v1/product-categories/types")
     */
    public function getTypes(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(ProductCategoryType::values());

        return $this->json(new Paginator($data));
    }
}
