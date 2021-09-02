<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use NewApiBundle\Entity\ProductCategory;
use NewApiBundle\InputType\InstitutionCreateInputType;
use NewApiBundle\InputType\ProductCategoryInputType;
use NewApiBundle\ProductCategoryService;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;

class ProductCategoryController extends AbstractController
{
    /** @var ProductCategoryService */
    private $productCategoryService;

    public function __construct(ProductCategoryService $productCategoryService)
    {
        $this->productCategoryService = $productCategoryService;
    }

    /**
     * @Rest\Get("/web-app/v1/product-categories/{id}")
     *
     * @param ProductCategory $institution
     *
     * @return JsonResponse
     */
    public function item(ProductCategory $institution): JsonResponse
    {
        return $this->json($institution);
    }

    /**
     * @Rest\Get("/web-app/v1/product-categories")
     *
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        $data = $this->getDoctrine()->getRepository(ProductCategory::class)
            ->findAll();

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Post("/web-app/v1/product-categories")
     *
     * @param ProductCategoryInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(ProductCategoryInputType $inputType): JsonResponse
    {
        $productCategory = $this->productCategoryService->create($inputType);

        return $this->json($productCategory);
    }
}
