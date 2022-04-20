<?php declare(strict_types=1);

namespace NewApiBundle\Controller\VendorApp;

use BeneficiaryBundle\Entity\Beneficiary;

use Symfony\Component\Serializer\SerializerInterface as Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use VoucherBundle\Entity\Product;
use Gaufrette\Adapter\AwsS3;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use VoucherBundle\Utils\ProductService;

class ProductController extends AbstractVendorAppController
{
    /** @var ProductService */
    private $productService;

    /**
     * @param ProductService $productService
     */
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Get Products.
     *
     * @Rest\Get("/vendor-app/v1/products")
     * @Security("is_granted('ROLE_USER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function all(Request $request): Response
    {
        $body = $request->request->all();
        $countryIso3 = $body['__country'];

        try {
            $products = $this->productService->findAll($countryIso3);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->json($products);
    }
}
