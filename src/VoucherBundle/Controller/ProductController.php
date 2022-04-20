<?php

namespace VoucherBundle\Controller;

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

/**
 * Class ProductController
 * @package VoucherBundle\Controller
 *
 * @SWG\Parameter(
 *     name="country",
 *     in="header",
 *     type="string",
 *     required=true
 * )
 */
class ProductController extends Controller
{

    /**
     * Create a new Product.
     *
     * @Rest\Put("/products", name="add_product")
     * @Security("is_granted('ROLE_DISTRIBUTIONS_DIRECTOR')")
     *
     * @SWG\Tag(name="Product")
     *
     * @SWG\Parameter(
     *     name="product",
     *     in="body",
     *     required=true,
     *     @Model(type=Product::class, groups={"FullProduct"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Product created",
     *     @Model(type=Product::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function createAction(Request $request)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');

        $productData = $request->request->all();

        try {
            $return = $this->get('voucher.product_service')->createFromArray($productData);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $productJson = $serializer->serialize(
            $return,
            'json',
            ['groups' => ['FullProduct']]
        );
        return new Response($productJson);
    }

    /**
     * Get Products.
     *
     * @Rest\Get("/products", name="get_products")
     * @Security("is_granted('ROLE_USER')")
     *
     * @SWG\Tag(name="Product")
     *
     * @SWG\Parameter(
     *     name="product",
     *     in="body",
     *     required=true,
     *     @Model(type=Product::class, groups={"FullProduct"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Product created",
     *     @Model(type=Product::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @return Response
     */
    public function getAction(Request $request)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');

        $body = $request->request->all();
        $countryIso3 = $body['__country'];

        try {
            $return = $this->get('voucher.product_service')->findAll($countryIso3);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $productJson = $serializer->serialize(
            $return,
            'json',
            ['groups' => ['FullProduct']]
        );
        return new Response($productJson);
    }

    /**
     * Get Products
     *
     * @Security("is_granted('ROLE_USER')")
     *
     * @SWG\Tag(name="Vendor App")
     *
     * @SWG\Parameter(
     *     name="product",
     *     in="body",
     *     required=true,
     *     @Model(type=Product::class, groups={"FullProduct"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Product created",
     *     @Model(type=Product::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @return Response
     */
    public function vendorGetAction(Request $request)
    {
        return $this->getAction($request);
    }

    /**
     * Update Products.
     *
     * @Rest\Post("/products/{id}", name="update_product")
     * @Security("is_granted('ROLE_DISTRIBUTIONS_DIRECTOR')")
     *
     * @SWG\Tag(name="Product")
     *
     * @SWG\Parameter(
     *     name="product",
     *     in="body",
     *     required=true,
     *     @Model(type=Product::class, groups={"FullProduct"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Product created",
     *     @Model(type=Product::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Product $product
     * @param Request $request
     * @return Response
     */
    public function updateAction(Product $product, Request $request)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');

        $productData = $request->request->all();

        try {
            $return = $this->get('voucher.product_service')->updateFromArray($product, $productData);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $productJson = $serializer->serialize(
            $return,
            'json',
            ['groups' => ['FullProduct']]
        );
        return new Response($productJson);
    }

    /**
     * Delete a Product.
     *
     * @Rest\Delete("/products/{id}", name="delete_product")
     * @Security("is_granted('ROLE_DISTRIBUTIONS_DIRECTOR')")
     *
     * @SWG\Tag(name="Product")
     *
     * @SWG\Parameter(
     *     name="product",
     *     in="body",
     *     required=true,
     *     @Model(type=Product::class, groups={"FullProduct"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Product created",
     *     @Model(type=Product::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Product $product
     * @return Response
     */
    public function deleteAction(Product $product)
    {
        try {
            $return = $this->get('voucher.product_service')->archive($product);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(json_encode($return));
    }

    /**
     * @Rest\Post("/products/upload/image", name="upload_image")
     * @Security("is_granted('ROLE_DISTRIBUTIONS_DIRECTOR')")
     *
     * @SWG\Tag(name="Product")
     *
     * @SWG\Parameter(
     *     name="file",
     *     in="formData",
     *     required=true,
     *     type="file"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Image uploaded",
     *     @SWG\Schema(
     *          type="string"
     *     )
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function uploadImageAction(Request $request)
    {
        $content = $request->getContent();
        $file = $request->files->get('file');

        $type = $file->getMimeType();
        if ($type !== 'image/gif' && $type !== 'image/jpeg' && $type !== 'image/png') {
            return new Response('The image type must be gif, png or jpg.', Response::HTTP_BAD_REQUEST);
        }

        $adapter = $this->container->get('knp_gaufrette.filesystem_map')->get('products')->getAdapter();
        $filename = $this->get('common.upload_service')->uploadImage($file, $adapter);
        $bucketName = $this->getParameter('aws_s3_bucket_name');
        $region = $this->getParameter('aws_s3_region');

        $return = 'https://s3.'.$region.'.amazonaws.com/'.$bucketName.'/products/'.$filename;
        return new Response(json_encode($return));
    }
}
