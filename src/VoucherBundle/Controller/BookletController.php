<?php

namespace VoucherBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use VoucherBundle\Entity\Booklet;
use Doctrine\Common\Collections\Collection;


/**
 * Class BookletController
 * @package VoucherBundle\Controller
 */
class BookletController extends Controller
{
    /**
     * Create a new Booklet.
     *
     * @Rest\Put("/booklets", name="add_booklet")
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
    public function createBookletAction(Request $request)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $bookletData = $request->request->all();

        try {
            $return = $this->get('voucher.booklet_service')->create($bookletData);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $bookletJson = $serializer->serialize(
            $return,
            'json',
            SerializationContext::create()->setGroups(['FullBooklet'])->setSerializeNull(true)
        );
        return new Response($bookletJson);
    }

    /**
     * Get all booklets
     *
     * @Rest\Get("/booklets", name="get_all_booklets")
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
    public function getAllAction(Request $request)
    {
        try {
            $booklets = $this->get('voucher.booklet_service')->findAll();
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')->serialize($booklets, 'json', SerializationContext::create()->setGroups(['FullBooklet'])->setSerializeNull(true));
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

        $json = $this->get('jms_serializer')->serialize($booklets, 'json', SerializationContext::create()->setGroups(['FullBooklet'])->setSerializeNull(true));
        return new Response($json);
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
        
        foreach($booklets as $booklet) {
            $bookletPasswords[] = [
                $booklet->getCode() => $booklet->getPassword()
            ];
        }

        $json = $this->get('jms_serializer')->serialize($bookletPasswords, 'json', SerializationContext::create()->setGroups(['FullBooklet'])->setSerializeNull(true));
        return new Response($json);
    }

    /**
     * Get single booklet
     *
     * @Rest\Get("/booklets/{id}", name="get_single_booklet")
     *
     * @SWG\Tag(name="Single Booklet")
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
        $json = $this->get('jms_serializer')->serialize($booklet, 'json', SerializationContext::create()->setGroups(['FullBooklet'])->setSerializeNull(true));

        return new Response($json);
    }

    /**
     * Edit a booklet {id} with data in the body
     *
     * @Rest\Post("/booklets/{id}", name="update_booklet")
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

        $json = $this->get('jms_serializer')->serialize($newBooklet, 'json', SerializationContext::create()->setGroups(['FullBooklet'])->setSerializeNull(true));
        return new Response($json);
    }

    /**
     * Deactivate booklets
     * @Rest\Post("/deactivate-booklets", name="deactivate_booklets")
     *
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
    public function deactivateBooklets(Request $request){
        try {
            $data = $request->request->all();
            $bookletCodes = $data['bookletCodes'];
            $this->get('voucher.booklet_service')->deactivateMany($bookletCodes);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(json_encode('Booklet successfully deactivated'));
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
    public function deactivateAction(Booklet $booklet) {
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
     *
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
    public function updatePasswordAction(Request $request)
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
     * @Rest\Post("/booklets/assign/{beneficiaryId}", name="assign_booklet")
     * @ParamConverter("booklet", options={"mapping": {"bookletId": "code"}})
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
     * @return Response
     */
    public function assignAction(Request $request, Beneficiary $beneficiary)
    {
        $code = $request->request->get('code');
        $booklet = $this->get('voucher.booklet_service')->getOne($code);
        try {
            $return = $this->get('voucher.booklet_service')->assign($booklet, $beneficiary);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(json_encode($return));
    }

    /**
     * To print a batch of booklets
     *
     * @Rest\Post("/booklets-print", name="print_booklets")
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
            return $this->get('voucher.booklet_service')->generatePdf([$booklet]);;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }
}
