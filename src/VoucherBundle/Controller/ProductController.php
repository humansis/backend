<?php

namespace VoucherBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\Product;
use Gaufrette\Adapter\AwsS3;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class ProductController
 * @package VoucherBundle\Controller
 */
class ProductController extends Controller
{

    /**
     * Create a new Product.
     *
     * @Rest\Put("/products", name="add_product")
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
    public function createProductAction(Request $request)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $productData = $request->request->all();

        try {
            $return = $this->get('voucher.product_service')->create($productData);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $productJson = $serializer->serialize(
            $return,
            'json',
            SerializationContext::create()->setGroups(['FullProduct'])->setSerializeNull(true)
        );
        return new Response($productJson);
    }

    /**
     * Get Products.
     *
     * @Rest\Get("/products", name="get_products")
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
    public function getProductAction()
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        try {
            $return = $this->get('voucher.product_service')->findAll();
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $productJson = $serializer->serialize(
            $return,
            'json',
            SerializationContext::create()->setGroups(['FullProduct'])->setSerializeNull(true)
        );
        return new Response($productJson);
    }

    /**
     * Update Products.
     *
     * @Rest\Post("/products/{id}", name="update_product")
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
    public function updateProductAction(Product $product, Request $request)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $productData = $request->request->all();

        try {
            $return = $this->get('voucher.product_service')->update($product, $productData);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $productJson = $serializer->serialize(
            $return,
            'json',
            SerializationContext::create()->setGroups(['FullProduct'])->setSerializeNull(true)
        );
        return new Response($productJson);
    }

    /**
     * Delete a Product.
     *
     * @Rest\Delete("/products/{id}", name="delete_product")
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
    public function deleteProductAction(Product $product)
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
     * 
     * @SWG\Tag(name="UploadImage")
     *
     * @SWG\Parameter(
     *     name="file",
     *     in="formData",
     *     required=true,
     *     type="file"
     * )
     * @SWG\Response(
     *     response=200,
     *     @SWG\Schema(
     *          type="string"
     *     )
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function uploadImage(Request $request) {
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
