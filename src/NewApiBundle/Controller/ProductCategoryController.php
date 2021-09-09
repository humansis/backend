<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use NewApiBundle\Component\Product\ProductCategoryService;
use NewApiBundle\Entity\ProductCategory;
use NewApiBundle\InputType\ProductCategoryInputType;
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
     * @param ProductCategory $productCategory
     *
     * @return JsonResponse
     */
    public function item(ProductCategory $productCategory): JsonResponse
    {
        return $this->json($productCategory);
    }

    /**
     * @Rest\Get("/web-app/v1/product-categories")
     * @Rest\Get("/vendor-app/v1/product-categories")
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
        $this->getDoctrine()->getManager()->persist($productCategory);
        $this->getDoctrine()->getManager()->flush();

        return $this->json($productCategory);
    }

    /**
     * @Rest\Post("/web-app/v1/product-categories/{id}")
     *
     * @param ProductCategory          $productCategory
     * @param ProductCategoryInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(ProductCategory $productCategory, ProductCategoryInputType $inputType): JsonResponse
    {
        $productCategory = $this->productCategoryService->update($productCategory, $inputType);
        $this->getDoctrine()->getManager()->persist($productCategory);
        $this->getDoctrine()->getManager()->flush();
        return $this->json($productCategory);
    }
}
