<?php

declare(strict_types=1);

namespace Controller;

use Controller\ExportController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\File\UploadService;
use InputType\ProductCreateInputType;
use InputType\ProductFilterInputType;
use InputType\ProductOrderInputType;
use InputType\ProductUpdateInputType;
use Request\Pagination;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Entity\Product;
use Repository\ProductRepository;

class ProductController extends AbstractController
{
    /** @var UploadService */
    private $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    /**
     * @Rest\Get("/web-app/v1/products/exports")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exports(Request $request): Response
    {
        $request->query->add([
            'products' => true,
        ]);
        $request->request->add([
            '__country' => $request->headers->get('country'),
        ]);

        return $this->forward(ExportController::class.'::exportAction', [], $request->query->all());
    }

    /**
     * @Rest\Get("/web-app/v1/products/{id}")
     * @Cache(lastModified="product.getLastModifiedAt()", public=true)
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
     * @Rest\Get("/web-app/v1/products")
     * @Rest\Get("/vendor-app/v2/products")
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
     * @Rest\Post("/web-app/v1/products")
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
     * @Rest\Put("/web-app/v1/products/{id}")
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
     * @Rest\Post("/web-app/v1/products/images")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function uploadImage(Request $request): JsonResponse
    {
        if (!($file = $request->files->get('file'))) {
            throw new BadRequestHttpException('File missing.');
        }

        if (!in_array($file->getMimeType(), ['image/gif', 'image/jpeg', 'image/png'])) {
            throw new BadRequestHttpException('Invalid file type.');
        }

        $url = $this->uploadService->upload($file, 'products');

        return $this->json(['url' => $url]);
    }

    /**
     * @Rest\Delete("/web-app/v1/products/{id}")
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
