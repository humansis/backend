<?php

namespace VoucherBundle\Controller;

use NewApiBundle\Entity\Beneficiary;
use CommonBundle\InputType;
use NewApiBundle\Entity\Assistance;
use Doctrine\Common\Collections\Collection;
use FOS\RestBundle\Controller\Annotations as Rest;

use Symfony\Component\Serializer\SerializerInterface as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use VoucherBundle\Entity\Booklet;

/**
 * Class BookletController
 * @package VoucherBundle\Controller
 *
 * @SWG\Parameter(
 *     name="country",
 *     in="header",
 *     type="string",
 *     required=true
 * )
 */
class BookletController extends Controller
{
    /**
     * Used with another endpoint
     *
     * Get booklets that have been deactivated
     *
     * @Rest\Get("/deactivated-booklets", name="get_deactivated_booklets")
     *
     * @SWG\Tag(name="Booklets")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Booklets delivered",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Booklet::class, groups={"FullBooklet"}))
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
    public function getDeactivatedAction(Request $request)
    {
        try {
            $booklets = $this->get('voucher.booklet_service')->findDeactivated();
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('serializer')->serialize($booklets, 'json', ['groups' => ['FullBooklet']]);
        return new Response($json);
    }

    /**
     * Get booklets that have been deactivated
     *
     * @Rest\Get("/vendor-app/v1/deactivated-booklets")
     *
     * @SWG\Tag(name="Vendor App")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Booklets delivered",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Booklet::class, groups={"FullBooklet"}))
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
    public function vendorGetDeactivatedAction(Request $request)
    {
        return $this->getDeactivatedAction($request);
    }

    /**
     * used with another endpoint
     *
     * Get booklets that are protected by a password
     *
     * @Rest\Get("/protected-booklets", name="get_protected_booklets")
     *
     * @SWG\Tag(name="Booklets")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Booklets delivered",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Booklet::class, groups={"FullBooklet"}))
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
    public function getProtectedAction(Request $request)
    {
        try {
            $booklets = $this->get('voucher.booklet_service')->findProtected();
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $bookletPasswords = [];
        
        foreach ($booklets as $booklet) {
            $bookletPasswords[] = [
                $booklet->getCode() => $booklet->getPassword()
            ];
        }

        $json = $this->get('serializer')->serialize($bookletPasswords, 'json', ['groups' => ['FullBooklet']]);
        return new Response($json);
    }

    /**
     * Get booklets that are protected by a password
     *
     * @Rest\Get("/vendor-app/v1/protected-booklets")
     *
     * @SWG\Tag(name="Vendor App")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Booklets delivered",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Booklet::class, groups={"FullBooklet"}))
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
    public function vendorGetProtectedAction(Request $request)
    {
        return $this->getProtectedAction($request);
    }

    /**
     * Deactivate booklets
     * @Rest\Post("/deactivate-booklets", name="deactivate_booklets")
     * @Security("is_granted('ROLE_USER')")
     * @SWG\Tag(name="Booklets")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Success or not",
     *     @SWG\Schema(type="boolean")
     * )
     *
     * @return Response
     */
    public function deactivateBookletsAction(Request $request)
    {
        try {
            $data = $request->request->all();
            $bookletCodes = $data['bookletCodes'];
            $this->get('voucher.booklet_service')->deactivateMany($bookletCodes);
        } catch (\Exception $exception) {
            $this->container->get('logger')->error('exception', [$exception->getMessage()]);
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(json_encode('Booklet successfully deactivated'));
    }

    /**
     * Deactivate booklets
     *
     * @Rest\Post("/vendor-app/v1/deactivate-booklets")
     *
     * @Security("is_granted('ROLE_USER')")
     *
     * @SWG\Tag(name="Vendor App")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Success or not",
     *     @SWG\Schema(type="boolean")
     * )
     *
     * @return Response
     */
    public function vendorDeactivateBookletsAction(Request $request)
    {
        return $this->deactivateBookletsAction($request);
    }

    /**
     * Used with another endpoint
     *
     * Deactivate a booklet
     * @Rest\Delete("/deactivate-booklets/{id}", name="deactivate_booklet")
     *
     * @SWG\Tag(name="Booklets")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Success or not",
     *     @SWG\Schema(type="boolean")
     * )
     *
     * @param Booklet $booklet
     * @return Response
     */
    public function deactivateAction(Booklet $booklet)
    {
        try {
            $this->get('voucher.booklet_service')->deactivate($booklet);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(json_encode('Booklet successfully deactivated'));
    }

    /**
     * Used with another endpoint
     *
     * Assign the booklet to a specific beneficiary
     * @Rest\Post("/booklets/assign/{distributionId}/{beneficiaryId}", name="assign_booklet")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_ASSIGN')")
     * @ParamConverter("booklet", options={"mapping": {"bookletId": "code"}})
     * @ParamConverter("assistance", options={"mapping": {"distributionId": "id"}})
     * @ParamConverter("beneficiary", options={"mapping": {"beneficiaryId": "id"}})
     *
     * @SWG\Tag(name="Booklets")
     *
     * @SWG\Response(
     *     response=200,
     *     description="SUCCESS",
     *     @SWG\Schema(type="string")
     * )
     *
     * @param Booklet $booklet
     * @param Beneficiary $beneficiary
     * @param Assistance $assistance
     * @return Response
     */
    public function assignAction(Request $request, Assistance $assistance, Beneficiary $beneficiary)
    {
        $code = $request->request->get('code');
        $booklet = $this->get('voucher.booklet_service')->getOne($code);
        try {
            $return = $this->get('voucher.booklet_service')->assign($booklet, $assistance, $beneficiary);
        } catch (\Exception $exception) {
            $this->container->get('logger')->error('exception', [$exception->getMessage()]);
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(json_encode($return));
    }

    /**
     * Assign the booklet to a specific beneficiary.
     *
     * @Rest\Post("/offline-app/v1/booklets/assign/{distributionId}/{beneficiaryId}")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_ASSIGN')")
     * @ParamConverter("booklet", options={"mapping": {"bookletId": "code"}})
     * @ParamConverter("assistance", options={"mapping": {"distributionId": "id"}})
     * @ParamConverter("beneficiary", options={"mapping": {"beneficiaryId": "id"}})
     *
     * @SWG\Tag(name="Offline App")
     * @SWG\Tag(name="Booklets")
     *
     * @SWG\Response(response=200, description="SUCCESS", @SWG\Schema(type="string"))
     *
     * @param Request          $request
     * @param Assistance $assistance
     * @param Beneficiary      $beneficiary
     * @return Response
     */
    public function offlineAssignAction(Request $request, Assistance $assistance, Beneficiary $beneficiary)
    {
        return $this->assignAction($request, $assistance, $beneficiary);
    }
}
