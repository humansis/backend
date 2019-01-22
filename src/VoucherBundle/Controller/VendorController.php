<?php

namespace VoucherBundle\Controller;

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
use VoucherBundle\Entity\Vendor;

/**
 * Class VendorController
 * @package VoucherBundle\Controller
 */
class VendorController extends Controller
{
    /**
     * Create a new Vendor. You must have called getSalt before use this one
     *
     * @Rest\Put("/new_vendor", name="add_vendor")
     *
     * @SWG\Tag(name="Vendors")
     *
     * @SWG\Parameter(
     *     name="vendor",
     *     in="body",
     *     required=true,
     *     @Model(type=Vendor::class, groups={"FullVendor"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Vendor created",
     *     @Model(type=User::class)
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
    public function createVendor(Request $request)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');
        
        $vendor = $request->request->all();
        $vendorData = $vendor;
        $vendor = $serializer->deserialize(json_encode($request->request->all()), Vendor::class, 'json');

        try
        {
            $return = $this->get('voucher.voucher_service')->create($vendor, $vendorData);
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage(), 500);
        }

        $vendorJson = $serializer->serialize(
            $return,
            'json',
            SerializationContext::create()->setGroups(['FullVendor'])->setSerializeNull(true)
        );
        return new Response($vendorJson);
    }

    /**
     * Get all vendors
     *
     * @Rest\Get("/vendors", name="get_all_vendors")
     *
     * @SWG\Tag(name="Vendors")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Vendors delivered",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Vendor::class, groups={"FullVendor"}))
     *     )
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
    public function getAllAction(Request $request)
    {
        $vendors = $this->get('voucher.voucher_service')->findAll();
        $json = $this->get('jms_serializer')->serialize($vendors, 'json', SerializationContext::create()->setGroups(['FullVendor'])->setSerializeNull(true));
        
        return new Response($json);
    }

    /**
     * Get single vendor
     *
     * @Rest\Get("/vendors/{id}", name="get_single_vendor")
     *
     * @SWG\Tag(name="Single Vendor")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Vendor delivered",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Vendor::class, groups={"FullVendor"}))
     *     )
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Vendor $vendor
     * @return Response
     */
    public function getSingleVendor(Vendor $vendor)
    {
        $json = $this->get('jms_serializer')->serialize($vendor, 'json', SerializationContext::create()->setGroups(['FullVendor'])->setSerializeNull(true));
        
        return new Response($json);
    }


    /**
     * Edit a vendor {id} with data in the body
     *
     * @Rest\Post("/vendors/{id}", name="update_vendor")
     *
     * @SWG\Tag(name="Vendors")
     *
     * @SWG\Parameter(
     *     name="vendor",
     *     in="body",
     *     type="string",
     *     required=true,
     *     description="fields of the vendor which must be updated",
     *     @Model(type=Vendor::class)
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="SUCCESS",
     *     @Model(type=User::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @param Vendor $vendor
     * @return Response
     */
    public function updateAction(Request $request, Vendor $vendor)
    {
        $vendorData = $request->request->all();
        $newVendor = $this->get('voucher.voucher_service')->update($vendor, $vendorData);
        $json = $this->get('jms_serializer')->serialize($newVendor, 'json', SerializationContext::create()->setGroups(['FullVendor'])->setSerializeNull(true));
        return new Response($json);
    }
}
