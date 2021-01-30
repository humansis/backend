<?php

namespace CommonBundle\Controller;

use CommonBundle\Utils\LocationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use BeneficiaryBundle\Entity\Camp;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class LocationController
 * @package CommonBundle\Controller
 *
 * @SWG\Parameter(
 *     name="country",
 *     in="header",
 *     type="string",
 *     required=true
 * )
 */
class LocationController extends Controller
{
    /** @var SerializerInterface */
    private $serializer;
    /** @var LocationService */
    private $locationService;

    /**
     * LocationController constructor.
     *
     * @param SerializerInterface $serializer
     * @param LocationService     $locationService
     */
    public function __construct(SerializerInterface $serializer, LocationService $locationService)
    {
        $this->serializer = $serializer;
        $this->locationService = $locationService;
    }

    /**
     * @Rest\Get("/location/adm1", name="all_adm1")
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
    public function getAllAdm1(Request $request)
    {
        $filters = $request->request->all();
        $adm1 = $this->locationService->getAllAdm1($filters['__country']);

        $json = $this->serializer
            ->serialize(
                $adm1,
                'json',
                ['groups' => ["SmallHousehold"], 'datetime_format' => 'd-m-Y']
            );
        return new Response($json);
    }

    /**
     * @Rest\Post("/location/adm2", name="all_adm2")
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
    public function getAllAdm2(Request $request)
    {
        $filters = $request->request->all();
        $adm2 = $this->locationService->getAllAdm2($filters['adm1']);

        $json = $this->serializer
            ->serialize(
                $adm2,
                'json',
                ['groups' => ["SmallHousehold"], 'datetime_format' => 'd-m-Y']
            );
        return new Response($json);
    }

    /**
     * @Rest\Post("/location/adm3", name="all_adm3")
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
    public function getAllAdm3(Request $request)
    {
        $filters = $request->request->all();
        $adm3 = $this->locationService->getAllAdm3($filters['adm2']);

        $json = $this->serializer
            ->serialize(
                $adm3,
                'json',
                ['groups' => ["SmallHousehold"], 'datetime_format' => 'd-m-Y']
            );
        return new Response($json);
    }

    /**
     * @Rest\Post("/location/adm4", name="all_adm4")
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
    public function getAllAdm4(Request $request)
    {
        $filters = $request->request->all();
        $adm4 = $this->locationService->getAllAdm4($filters['adm3']);

        $json = $this->serializer
            ->serialize(
                $adm4,
                'json',
                ['groups' => ["SmallHousehold"], 'datetime_format' => 'd-m-Y']
            );
        return new Response($json);
    }

     /**
     * @Rest\Post("/location/camps", name="all_camps")
     *
     * @SWG\Tag(name="Location")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All camps",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Camp::class))
     *     )
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function getAllCamps(Request $request)
    {
        $filters = $request->request->all();
        $camps = $this->locationService->getAllCamps($filters);

        $json = $this->serializer
            ->serialize(
                $camps,
                'json',
                ['groups' => ["FullCamp"]]
            );
        return new Response($json);
    }


    /**
    * @Rest\Get("/location/upcoming_distribution", name="all_location")
    *
    * @SWG\Tag(name="Location")
    *
    * @SWG\Response(
    *     response=200,
    *     description="All location",
    * )
    *
    * @param Request $request
    * @return Response
    */
    public function getCodeUpcomingDistribution(Request $request)
    {
        $filters = $request->request->all();

        $location = $this->locationService->getCodeOfUpcomingDistribution($filters['__country']);

        $json = $this->serializer
            ->serialize(
                $location,
                'json',
                ['groups' => ["SmallHousehold"], 'datetime_format' => 'd-m-Y']
            );
        return new Response($json);
    }
}
