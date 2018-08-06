<?php

namespace CommonBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use CommonBundle\Entity\Adm1;
use JMS\Serializer\SerializationContext;

class LocationController extends Controller
{

    /**
     * @Rest\get("/location/adm1", name="all_adm1")
     * 
     * @SWG\Tag(name="Location")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All Province (adm1)",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Adm1::class))
     *     )
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function getAllAdm1(Request $request) {
        $filters = $request->request->all();
        $locationService = $this->get('location_service');
        $adm1 = $locationService->getAllAdm1($filters['__country']);

        $json = $this->get('jms_serializer')
            ->serialize(
                $adm1,
                'json',
                SerializationContext::create()->setGroups("SmallHousehold")->setSerializeNull(true)
            );
        return new Response($json);
    }

    /**
     * @Rest\post("/location/adm2", name="all_adm2")
     * 
     * @SWG\Tag(name="Location")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All District (adm2)",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Adm2::class))
     *     )
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function getAllAdm2(Request $request) {
        $filters = $request->request->all();
        $locationService = $this->get('location_service');
        $adm2 = $locationService->getAllAdm2($filters['adm1']);

        $json = $this->get('jms_serializer')
            ->serialize(
                $adm2,
                'json',
                SerializationContext::create()->setGroups("SmallHousehold")->setSerializeNull(true)
            );
        return new Response($json);
    }

    /**
     * @Rest\post("/location/adm3", name="all_adm3")
     * 
     * @SWG\Tag(name="Location")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All Commune (adm3)",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Adm3::class))
     *     )
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function getAllAdm3(Request $request) {
        $filters = $request->request->all();
        $locationService = $this->get('location_service');
        $adm3 = $locationService->getAllAdm3($filters['adm2']);

        $json = $this->get('jms_serializer')
            ->serialize(
                $adm3,
                'json',
                SerializationContext::create()->setGroups("SmallHousehold")->setSerializeNull(true)
            );
        return new Response($json);
    }

    /**
     * @Rest\post("/location/adm4", name="all_adm4")
     * 
     * @SWG\Tag(name="Location")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All village (adm4)",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Adm4::class))
     *     )
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function getAllAdm4(Request $request) {
        $filters = $request->request->all();
        $locationService = $this->get('location_service');
        $adm4 = $locationService->getAllAdm4($filters['adm3']);

        $json = $this->get('jms_serializer')
            ->serialize(
                $adm4,
                'json',
                SerializationContext::create()->setGroups("SmallHousehold")->setSerializeNull(true)
            );
        return new Response($json);
    }
}
