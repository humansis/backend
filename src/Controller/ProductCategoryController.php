<?php

declare(strict_types=1);

namespace Controller;

use Pagination\Paginator;
use Component\Product\ProductCategoryService;
use Entity\ProductCategory;
use InputType\ProductCategoryFilterInputType;
use InputType\ProductCategoryInputType;
use InputType\ProductCategoryOrderInputType;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Entity\Product;

class ProductCategoryController extends AbstractController
{
    public function __construct(private readonly ProductCategoryService $productCategoryService)
    {
    }

    /**
     * @Rest\Get("/web-app/v1/product-categories/{id}")
     *
     *
     */
    public function item(ProductCategory $productCategory): JsonResponse
    {
        return $this->json($productCategory);
    }

    /**
     * @Rest\Get("/web-app/v1/product-categories")
     * @Rest\Get("/vendor-app/v1/product-categories")
     */
    public function list(
        Request $request,
        ProductCategoryFilterInputType $filter,
        ProductCategoryOrderInputType $sort
    ): JsonResponse {
        $data = $this->getDoctrine()->getRepository(ProductCategory::class)
            ->findByFilter($filter, $sort);

        return $this->json($data);
    }

    /**
     * @Rest\Post("/web-app/v1/product-categories")
     *
     *
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
     *
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
     *
     */
    public function delete(ProductCategory $productCategory): JsonResponse
    {
        $productCount = $this->getDoctrine()->getManager()->getRepository(Product::class)->count(
            ['productCategory' => $productCategory]
        );
        if ($productCount > 0) {
            throw new BadRequestHttpException("You can't delete category with products");
        }
        $this->productCategoryService->archive($productCategory);
        $this->getDoctrine()->getManager()->persist($productCategory);
        $this->getDoctrine()->getManager()->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
