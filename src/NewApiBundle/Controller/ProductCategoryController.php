<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use NewApiBundle\Component\Product\ProductCategoryService;
use NewApiBundle\Entity\ProductCategory;
use NewApiBundle\InputType\ProductCategoryFilterInputType;
use NewApiBundle\InputType\ProductCategoryInputType;
use NewApiBundle\InputType\ProductCategoryOrderInputType;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use VoucherBundle\Entity\Product;

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
    public function list(Request $request, ProductCategoryFilterInputType $filter, ProductCategoryOrderInputType $sort): JsonResponse
    {
        $data = $this->getDoctrine()->getRepository(ProductCategory::class)
            ->findByFilter($filter, $sort);

        return $this->json($data);
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

    /**
     * @Rest\Delete("/web-app/v1/product-categories/{id}")
     *
     * @param ProductCategory $productCategory
     *
     * @return JsonResponse
     */
    public function delete(ProductCategory $productCategory): JsonResponse
    {
        $this->productCategoryService->archive($productCategory);
        $this->getDoctrine()->getManager()->persist($productCategory);
        $this->getDoctrine()->getManager()->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
