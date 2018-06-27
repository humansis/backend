<?php

namespace DistributionBundle\UtilsController;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;

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
     * 	  @SWG\Schema(ref=@Model(type=Project::class))
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Project created",
     *     @Model(type=Project::class)
     * )
     * 
     * @param Request $request
     * @return Response
     */
    public function addAction(Request $request)
    {
        $distributionArray = $request->request->all();
        dump($distributionArray);

        try
        {
            $distribution = $this->get('project.distribution_service')->create($distributionArray);
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')->serialize($distribution, 'json');

        return new Response($json);
    }
}