<?php

namespace VoucherBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class VoucherController
 * @package VoucherBundle\Controller
 */
class VoucherController extends Controller
{
    /**
     * Create a new Vendor. You must have called getSalt before use this one
     *
     * @Rest\Put("/vendors", name="add_vendor")
     * @Security("is_granted('ROLE_USER_MANAGEMENT_WRITE')")
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
        var_dump("test");
        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $vendor = $request->request->all();
        $vendorData = $vendor;
        var_dump($vendor);
        var_dump($vendorData);
        // $user = $serializer->deserialize(json_encode($request->request->all()), User::class, 'json');

        // try
        // {
        //     $return = $this->get('user.user_service')->create($user, $userData);
        // }
        // catch (\Exception $exception)
        // {
        //     return new Response($exception->getMessage(), 500);
        // }

        // if (!$user instanceof User)
        //     return new JsonResponse($user);

        // $userJson = $serializer->serialize(
        //     $return,
        //     'json',
        //     SerializationContext::create()->setGroups(['FullUser'])->setSerializeNull(true)
        // );
        // return new Response($userJson);
        return $vendor;
    }
}
