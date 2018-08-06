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
        dump($adm1);

        $json = $this->get('jms_serializer')
            ->serialize(
                $adm1,
                'json',
                SerializationContext::create()->setGroups("SmallHousehold")->setSerializeNull(true)
            );
        return new Response($json);
    }
}
