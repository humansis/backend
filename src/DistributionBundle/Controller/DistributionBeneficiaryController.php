<?php

namespace DistributionBundle\Controller;

use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;


use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

class DistributionBeneficiaryController extends Controller
{
      /**
     * Create a distributionBeneficiary
     * @Rest\Put("/distribution/beneficiaries", name="add_distribution_benefeciaries")
     * 
     * @SWG\Tag(name="DistributionBeneficiaries")
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
        $distributionBeneficiaryArray = $request->request->all();

        try
        {
            $distribution = $this->get('distribution.distribution_beneficiary_service')->create($distributionBeneficiaryArray);
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')->serialize($distribution, 'json', SerializationContext::create()->setSerializeNull(true));

        return new Response($json);
    }
}