<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use CommonBundle\Pagination\Paginator;
use NewApiBundle\Enum\Domain;
use NewApiBundle\Enum\ProductCategoryType;
use NewApiBundle\Services\CodeListService;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductCategoryCodelistController extends AbstractController
{
    /** @var CodeListService */
    private $codeListService;

    public function __construct(CodeListService $codeListService)
    {
        $this->codeListService = $codeListService;
    }

    /**
     * @Rest\Get("/web-app/v1/product-categories/types")
     *
     * @return JsonResponse
     */
    public function getTypes(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(ProductCategoryType::values(), Domain::ENUMS);

        return $this->json(new Paginator($data));
    }
}
