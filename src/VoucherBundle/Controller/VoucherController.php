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
use VoucherBundle\Entity\Voucher;

/**
 * Class VoucherController
 * @package VoucherBundle\Controller
 */
class VoucherController extends Controller
{
    /**
     * Create a new Voucher.
     *
     * @Rest\Put("/new_voucher", name="add_voucher")
     *
     * @SWG\Tag(name="Vouchers")
     *
     * @SWG\Parameter(
     *     name="voucher",
     *     in="body",
     *     required=true,
     *     @Model(type=Voucher::class, groups={"FullVoucher"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Voucher created",
     *     @Model(type=Voucher::class)
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
    public function createVoucher(Request $request)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');
        $voucherData = $request->request->all();
        try {
            $return = $this->get('voucher.voucher_service')->create($voucherData);
        } catch (\Exception $exception) {
            var_dump($exception->getMessage());
            return new Response($exception->getMessage(), 500);
        }

        // $vendorJson = $serializer->serialize(
        //     $return,
        //     'json',
        //     SerializationContext::create()->setGroups(['FullVendor'])->setSerializeNull(true)
        // );
        // return new Response($booklet);
    }
}
