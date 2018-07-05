<?php

namespace DistributionBundle\Controller;

use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use DistributionBundle\Entity\DistributionData;

use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

class DistributionController extends Controller
{
    /**
     * Create a distribution
     * @Rest\Put("/distributions", name="add_distribution")
     * 
     * @SWG\Tag(name="Distributions")
     * 
          * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="object",
     *      required=true,
     *      description="Body of the request",
     * 	  @SWG\Schema(ref=@Model(type=DistributionData::class))
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Project created",
     *     @Model(type=DistributionData::class)
     * )
     * 
     * @param Request $request
     * @return Response
     */
    public function addAction(Request $request)
    {
        $distributionArray = $request->request->all();

        try
        {
            $distribution = $this->get('distribution.distribution_service')->create($distributionArray);
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')->serialize($distribution, 'json', SerializationContext::create()->setSerializeNull(true));

        return new Response($json);
    }


    /**
     * @Rest\Get("/distributions", name="get_all_distributions")
     *
     * @SWG\Tag(name="distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All distributions",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=DistributionData::class))
     *     )
     * )
     * 
     * @param Request $request
     * @return Response
     */
    public function getAllAction(Request $request)
    {
        $distributions = $this->get('distribution.distribution_service')->findAll();
        $json = $this->get('jms_serializer')->serialize($distributions, 'json');

        return new Response($json);
    }

    /**
     * @Rest\Get("/distributions/{id}", name="get_one_distributions")
     *
     * @SWG\Tag(name="distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="one distribution",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=DistributionData::class))
     *     )
     * )
     *
     * @param DistributionData $DistributionData
     * @return Response
     */
    public function getOneAction(DistributionData $DistributionData)
    {
        $json = $this->get('jms_serializer')->serialize($DistributionData, 'json');

        return new Response($json);
    }



    /**
     * Edit a distribution
     * @Rest\Post("/distributions/{id}", name="update_distribution")
     *
     * @SWG\Tag(name="distributions")
     *
     * @SWG\Parameter(
     *     name="DistributionData",
     *     in="body",
     *     required=true,
     *     @Model(type=DistributionData::class)
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="distribution updated",
     *     @Model(type=DistributionData::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @param DistributionData $DistributionData
     * @return Response
     */
    public function updateAction(Request $request, DistributionData $DistributionData)
    {
        $distributionArray = $request->request->all();
        try
        {
            $DistributionData = $this->get('distribution.distribution_service')->edit($DistributionData, $distributionArray);
        }
        catch (\Exception $e)
        {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        $json = $this->get('jms_serializer')
            ->serialize($DistributionData, 'json', SerializationContext::create()->setSerializeNull(true));
        return new Response($json, Response::HTTP_OK);
    }

    /**
     * Archive a distribution
     * @Rest\Post("/distributions/archive/{id}", name="archived_project")
     *
     * @SWG\Tag(name="distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param DistributionData $distribution
     * @return Response
     */
    public function archivedAction(DistributionData $distribution)
    {
        try
        {
            $archivedDistribution = $this->get('distribution.distribution_service')->archived($distribution);
        }
        catch (\Exception $e)
        {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        $json = $this->get('jms_serializer')
            ->serialize($archivedDistribution, 'json', SerializationContext::create()->setSerializeNull(true));
        return new Response($json, Response::HTTP_OK);
    }


}