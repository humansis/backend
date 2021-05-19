<?php

namespace VoucherBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\InputType;
use DistributionBundle\Entity\Assistance;
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
     * Create a new Booklet.
     *
     * @Rest\Put("/booklets", name="add_booklet")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Booklets")
     *
     * @SWG\Parameter(
     *     name="booklet",
     *     in="body",
     *     required=true,
     *     @Model(type=Booklet::class, groups={"FullBooklet"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Booklet created",
     *     @Model(type=Booklet::class)
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
        $bookletData = $request->request->all();

        $lastBooklet = $this->get('voucher.booklet_service')->backgroundCreate($request->request->get('__country'), $bookletData);

        return new JsonResponse($lastBooklet);
    }

    /**
     * Create a new Booklet (synchronous).
     *
     * @Rest\Put("/booklets/sync", name="add_booklet_sync")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Booklets")
     *
     * @SWG\Parameter(
     *     name="booklet",
     *     in="body",
     *     required=true,
     *     @Model(type=Booklet::class, groups={"FullBooklet"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Booklet created",
     *     @Model(type=Booklet::class)
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
    public function createBookletSyncAction(Request $request)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');

        $bookletData = $request->request->all();

        try {
            $return = $this->get('voucher.booklet_service')->create($request->request->get('__country'), $bookletData);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $bookletJson = $serializer->serialize(
            $return,
            'json',
            ['groups' => ['FullBooklet']]
        );

        return new Response($bookletJson);
    }

    /**
     * Get number of inserted booklets.
     *
     * @Rest\Get("/booklets/inserted/{id}", name="inserted_booklets")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Booklets")
     *
     * @SWG\Parameter(
     *     name="booklet",
     *     in="body",
     *     required=true,
     *     @Model(type=Booklet::class, groups={"FullBooklet"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Booklet created",
     *     @Model(type=Booklet::class)
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
    public function getNumberOfInsertedBooklets(Request $request, Booklet $booklet)
    {
        try {
            $nbBooklets = $this->get('voucher.booklet_service')->getNumberOfInsertedBooklets($request->request->get('__country'), $booklet->getId());
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response($nbBooklets);

    }

    /**
     * Get all booklets (paginated).
     *
     * @Rest\Post("/booklets/get/all", name="all_booklets")
     *
     * @SWG\Tag(name="Booklets")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All booklets",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Booklet::class))
     *     )
     * )
     *
     * @param InputType\Country $country
     * @param InputType\DataTableType $filterType
     * @return Response
     */
    public function allAction(InputType\Country $country, InputType\DataTableType $filterType)
    {

        try {
            $booklets = $this->get('voucher.booklet_service')->getAll($country, $filterType);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $json = $this->get('serializer')->serialize(
            $booklets,
            'json',
            ['groups' => ["FullBooklet"]]
        );
        return new Response($json);
    }

    /**
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
     * Get single booklet
     *
     * @Rest\Get("/booklets/{id}", name="get_single_booklet")
     *
     * @SWG\Tag(name="Booklets")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Booklet delivered",
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
     * @param Booklet $booklet
     * @return Response
     */
    public function getSingleBookletAction(Booklet $booklet)
    {
        $json = $this->get('serializer')->serialize($booklet, 'json', ['groups' => ['FullBooklet']]);

        return new Response($json);
    }

    /**
     * Edit a booklet {id} with data in the body
     *
     * @Rest\Post("/booklets/{id}", name="update_booklet")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Booklets")
     *
     * @SWG\Parameter(
     *     name="booklet",
     *     in="body",
     *     type="string",
     *     required=true,
     *     description="fields of the booklet which must be updated",
     *     @Model(type=Booklet::class)
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="SUCCESS",
     *     @Model(type=Booklet::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @param Booklet $booklet
     * @return Response
     */
    public function updateAction(Request $request, Booklet $booklet)
    {
        $bookletData = $request->request->all();

        try {
            $newBooklet = $this->get('voucher.booklet_service')->update($booklet, $bookletData);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('serializer')->serialize($newBooklet, 'json', ['groups' => ['FullBooklet']]);
        return new Response($json);
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
     * Delete a booklet
     * @Rest\Delete("/booklets/{id}", name="delete_booklet")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
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
    public function deleteAction(Booklet $booklet)
    {
        try {
            $this->get('voucher.booklet_service')->deleteBookletFromDatabase($booklet);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(json_encode('Booklet successfully deleted'));
    }

    /**
     * Update password of the booklet
     * @Rest\Post("/booklets/update/password", name="update_password_booklet")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     * @SWG\Tag(name="Booklets")
     *
     * @SWG\Response(
     *     response=200,
     *     description="SUCCESS",
     *     @SWG\Schema(type="string")
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function postPasswordAction(Request $request)
    {
        $password = $request->request->get('password');
        $code = $request->request->get('code');
        $booklet = $this->get('voucher.booklet_service')->getOne($code);
        if (!isset($password) || empty($password)) {
            return new Response("The password is missing", Response::HTTP_BAD_REQUEST);
        }

        try {
            $return = $this->get('voucher.booklet_service')->updatePassword($booklet, $password);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(json_encode($return));
    }


    /**
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

    /**
     * To print a batch of booklets
     *
     * @Rest\Post("/booklets-print", name="print_booklets")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     * @SWG\Tag(name="Booklets")
     *
     * @SWG\Response(
     *     response=200,
     *     description="SUCCESS",
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
    public function printBookletsAction(Request $request)
    {
        $bookletData = $request->request->all();
        $bookletIds = $bookletData['bookletIds'];

        try {
            return $this->get('voucher.booklet_service')->printMany($bookletIds);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * To print a booklet
     *
     * @Rest\Get("/booklets/print/{id}", name="print_booklet")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     * @SWG\Tag(name="Booklets")
     *
     * @SWG\Response(
     *     response=200,
     *     description="SUCCESS",
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Booklet $booklet
     * @return Response
     */
    public function printBookletAction(Booklet $booklet)
    {
        try {
            return $this->get('voucher.booklet_service')->generatePdf([$booklet]);
            ;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
