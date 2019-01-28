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
     * @Rest\Put("/new_booklet", name="add_booklet")
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
    public function createBooklet(Request $request)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $booklet = $request->request->all();
        $bookletData = $booklet;
        $booklet = $serializer->deserialize(json_encode($request->request->all()), Booklet::class, 'json');

        try {
            $bookletBatch = $this->get('booklet.booklet_service')->getBookletBatch();
            $currentBatch = $bookletBatch;
            $counter = 1;
            for ($x = 0; $x < $bookletData['numberBooklets']; $x++) {
                $return = $this->get('booklet.booklet_service')->create($booklet, $bookletData, $currentBatch, $bookletBatch);
                $counter++;
                $currentBatch++;
            };
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), 500);
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
        $booklets = $this->get('booklet.booklet_service')->findAll();
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
    public function getSingleBooklet(Booklet $booklet)
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
        $newBooklet = $this->get('booklet.booklet_service')->update($booklet, $bookletData);
        $json = $this->get('jms_serializer')->serialize($newBooklet, 'json', SerializationContext::create()->setGroups(['FullBooklet'])->setSerializeNull(true));
        return new Response($json);
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
        $isSuccess = $this->get('booklet.booklet_service')->deleteFromDatabase($booklet);
        return new Response(json_encode($isSuccess));
    }

    
}
