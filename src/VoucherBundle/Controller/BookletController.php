<?php

namespace VoucherBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use VoucherBundle\Entity\Booklet;

/**
 * Class BookletController
 * @package VoucherBundle\Controller
 */
class BookletController extends Controller
{
    /**
     * Create a new Booklet.
     *
     * @Rest\Put("/booklet", name="add_booklet")
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
            $return = $this->get('booklet.booklet_service')->create($bookletData);
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
            $booklets = $this->get('booklet.booklet_service')->findAll();
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')->serialize($booklets, 'json', SerializationContext::create()->setGroups(['FullBooklet'])->setSerializeNull(true));
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
            $newBooklet = $this->get('booklet.booklet_service')->update($booklet, $bookletData);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')->serialize($newBooklet, 'json', SerializationContext::create()->setGroups(['FullBooklet'])->setSerializeNull(true));
        return new Response($json);
    }

    /**
     * Archive a booklet
     * @Rest\Delete("/booklets/{id}", name="archive_booklet")
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
    public function archiveAction(Booklet $booklet){
        try {
            $this->get('booklet.booklet_service')->archive($booklet);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(json_encode('Booklet successfully archived'));
    }

    /**
     * Delete a booklet
     * @Rest\Delete("/booklet/{id}", name="delete_booklet")
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
            $this->get('booklet.booklet_service')->deleteBookletFromDatabase($booklet);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(json_encode('Booklet successfully deleted'));
    }

    /**
     * Update password of the booklet
     * @Rest\Post("/booklets/{code}/password", name="update_password_booklet")
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
     * @param Booklet $booklet
     * @return Response
     */
    public function updatePasswordAction(Request $request, Booklet $booklet)
    {
        $password = $request->request->get('password');
         if (!isset($password) || empty($password)) {
            return new Response("The password is missing", Response::HTTP_BAD_REQUEST);
        }

        try {
            $return = $this->get('booklet.booklet_service')->updatePassword($booklet, $password);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(json_encode($return));
    }


    /**
     * Assign the booklet to a specific beneficiary
     * @Rest\Post("/booklets/assign/{id}", name="assign_booklet")
     *
     * @SWG\Tag(name="Booklets")
     *
     * @SWG\Response(
     *     response=200,
     *     description="SUCCESS",
     *     @SWG\Schema(type="string")
     * )
     *
     * @param Beneficiary $beneficiary
     * @param Request $request
     * @return Response
     */
    public function assignAction(Beneficiary $beneficiary, Request $request)
    {
        $allRequest = $request->request->all();
        if (!key_exists('booklet', $allRequest)) {
            return new Response("The booklet is missing", Response::HTTP_BAD_REQUEST);
        }

        $booklet = $request->request->get('booklet');

        try {
            $return = $this->get('booklet.booklet_service')->assign($booklet, $beneficiary);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(json_encode($return));
    }

}
