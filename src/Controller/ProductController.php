<?php

declare(strict_types=1);

namespace Controller;

use Controller\ExportController;
use Doctrine\Persistence\ManagerRegistry;
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
use Utils\ProductService;

class ProductController extends AbstractController
{
    public function __construct(private readonly UploadService $uploadService, private readonly ProductService $productService, private readonly ManagerRegistry $managerRegistry)
    {
    }

    #[Rest\Get('/web-app/v1/products/exports')]
    public function exports(Request $request): Response
    {
        $request->query->add([
            'products' => true,
        ]);
        $request->request->add([
            '__country' => $request->headers->get('country'),
        ]);

        return $this->forward(ExportController::class . '::exportAction', [], $request->query->all());
    }

    #[Rest\Get('/web-app/v1/products/{id}')]
    #[Cache(public: true, lastModified: 'product.getLastModifiedAt()')]
    public function item(Product $product): JsonResponse
    {
        if (true === $product->getArchived()) {
            throw $this->createNotFoundException();
        }

        return $this->json($product);
    }

    #[Rest\Get('/web-app/v1/products')]
    #[Rest\Get('/vendor-app/v2/products')]
    public function list(
        Request $request,
        ProductFilterInputType $filter,
        Pagination $pagination,
        ProductOrderInputType $orderBy
    ): JsonResponse {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        /** @var ProductRepository $repository */
        $repository = $this->managerRegistry->getRepository(Product::class);
        $data = $repository->findByCountry($request->headers->get('country'), $filter, $orderBy, $pagination);

        return $this->json($data);
    }

    #[Rest\Post('/web-app/v1/products')]
    public function create(ProductCreateInputType $inputType): JsonResponse
    {
        $object = $this->productService->create($inputType);

        return $this->json($object);
    }

    #[Rest\Put('/web-app/v1/products/{id}')]
    public function update(Product $product, ProductUpdateInputType $inputType): JsonResponse
    {
        $object = $this->productService->update($product, $inputType);

        return $this->json($object);
    }

    #[Rest\Post('/web-app/v1/products/images')]
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

    #[Rest\Delete('/web-app/v1/products/{id}')]
    public function delete(Product $product): JsonResponse
    {
        $this->productService->archive($product);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
