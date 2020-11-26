<?php

namespace NewApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\ProductCreateInputType;
use NewApiBundle\InputType\ProductUpdateInputType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use VoucherBundle\Entity\Product;

class ProductController extends AbstractController
{
    /**
     * @Rest\Get("/products/{id}")
     *
     * @param Product $product
     *
     * @return JsonResponse
     */
    public function item(Product $product): JsonResponse
    {
        if (true === $product->getArchived()) {
            throw $this->createNotFoundException();
        }

        return $this->json($product);
    }

    /**
     * @Rest\Post("/products")
     *
     * @param ProductCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(ProductCreateInputType $inputType): JsonResponse
    {
        $object = $this->get('voucher.product_service')->create($inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Put("/products/{id}")
     *
     * @param Product                $product
     * @param ProductUpdateInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(Product $product, ProductUpdateInputType $inputType): JsonResponse
    {
        $object = $this->get('voucher.product_service')->update($product, $inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Delete("/products/{id}")
     *
     * @param Product $product
     *
     * @return JsonResponse
     */
    public function delete(Product $product): JsonResponse
    {
        $this->get('voucher.product_service')->archive($product);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
