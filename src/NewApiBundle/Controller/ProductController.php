<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\File\UploadService;
use NewApiBundle\InputType\ProductCreateInputType;
use NewApiBundle\InputType\ProductFilterInputType;
use NewApiBundle\InputType\ProductOrderInputType;
use NewApiBundle\InputType\ProductUpdateInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use VoucherBundle\Entity\Product;
use VoucherBundle\Repository\ProductRepository;
use VoucherBundle\Utils\ProductService;

class ProductController extends AbstractController
{
    /** @var ProductService */
    private $productService;

    /** @var UploadService */
    private $uploadService;

    /**
     * ProductController constructor.
     *
     * @param ProductService $productService
     */
    public function __construct(ProductService $productService, UploadService $uploadService)
    {
        $this->productService = $productService;
        $this->uploadService = $uploadService;
    }

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
     * @Rest\Get("/products")
     *
     * @param Request                $request
     * @param ProductFilterInputType $filter
     * @param Pagination             $pagination
     * @param ProductOrderInputType  $orderBy
     *
     * @return JsonResponse
     */
    public function list(Request $request, ProductFilterInputType $filter, Pagination $pagination, ProductOrderInputType $orderBy): JsonResponse
    {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        /** @var ProductRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Product::class);
        $data = $repository->findByCountry($request->headers->get('country'), $filter, $orderBy, $pagination);

        return $this->json($data);
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
        $object = $this->productService->create($inputType);

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
        $object = $this->productService->update($product, $inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Post("/products/{id}/images")
     *
     * @param Product $product
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function uploadImage(Product $product, Request $request): JsonResponse
    {
        if (!($file = $request->files->get('file'))) {
            throw new BadRequestHttpException('File missing.');
        }

        if (!in_array($file->getMimeType(), ['image/gif', 'image/jpeg', 'image/png'])) {
            throw new BadRequestHttpException('Invalid file type.');
        }

        $url = $this->uploadService->upload($file, 'products');

        $product->setImage($url);

        $this->getDoctrine()->getManager()->persist($product);
        $this->getDoctrine()->getManager()->flush();

        return $this->json(['url' => $url]);
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
        $this->productService->archive($product);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
