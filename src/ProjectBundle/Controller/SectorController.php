<?php


namespace ProjectBundle\Controller;


use JMS\Serializer\SerializationContext;
use ProjectBundle\Entity\Sector;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;

use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

class SectorController extends Controller
{

    /**
     * @Rest\Get("/sectors", name="get_all_sectors")
     *
     * @SWG\Tag(name="Sectors")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All Sectors",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Sector::class))
     *     )
     * )
     */
    public function getAllAction(Request $request)
    {
        $sectors = $this->get('project.sector_service')->findAll();

        $json = $this->get('jms_serializer')
            ->serialize($sectors, 'json', SerializationContext::create()->setGroups(['FullSector'])->setSerializeNull(true));

        return new Response($json);
    }

    /**
     * @Rest\Get("/sectors/{id}", name="show_sector")
     *
     * @SWG\Tag(name="Sectors")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Sector asked",
     *     @Model(type=Sector::class)
     * )
     *
     * @param Sector $sector
     * @return Response
     */
    public function showAction(Sector $sector)
    {
        $json = $this->get('jms_serializer')
            ->serialize($sector, 'json', SerializationContext::create()->setGroups(['FullSector'])->setSerializeNull(true));

        return new Response($json);
    }

    /**
     * @Rest\Put("/sectors", name="add_sector")
     *
     * @SWG\Tag(name="Sectors")
     *
     * @SWG\Parameter(
     *     name="sector",
     *     in="body",
     *     required=true,
     *     @Model(type=Sector::class, groups={"FullSector"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Sector created",
     *     @Model(type=Sector::class)
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
    public function addAction(Request $request)
    {
        $sectorArray = $request->request->all();
        try
        {
            $sector = $this->get('project.sector_service')->create($sectorArray);
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')
            ->serialize($sector, 'json', SerializationContext::create()->setGroups(['FullSector'])->setSerializeNull(true));

        return new Response($json);
    }

    /**
     * @Rest\Post("/sectors/{id}", name="update_sector")
     *
     * @SWG\Tag(name="Sectors")
     *
     * @SWG\Parameter(
     *     name="Sector",
     *     in="body",
     *     required=true,
     *     @Model(type=Sector::class, groups={"FullSector"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Sector updated",
     *     @Model(type=Sector::class)
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
    public function updateAction(Request $request, Sector $sector)
    {
        $sectorArray = $request->request->all();
        try
        {
            $sector = $this->get('project.sector_service')->edit($sector, $sectorArray);
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')
            ->serialize($sector, 'json', SerializationContext::create()->setGroups(['FullSector'])->setSerializeNull(true));

        return new Response($json);
    }
}