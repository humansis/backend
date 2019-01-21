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
 * Class VoucherController
 * @package VoucherBundle\Controller
 */
class VoucherController extends Controller
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
     *     @Model(type=User::class, groups={"FullVendor"})
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
     *         @SWG\Items(ref=@Model(type=User::class, groups={"FullVendor"}))
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
}
