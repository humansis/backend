<?php


namespace DistributionBundle\Controller;


use DistributionBundle\Utils\CriteriaDistributionService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;


class CriteriaDistributionController extends Controller
{

    /**
     * @Rest\Post("/distribution/criteria")
     *
     *
     * @SWG\Tag(name="CriteriaDistributions")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     required=true,
     *     schema={}
     * )
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
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function getBeneficiariesAction(Request $request)
    {
        /** @var CriteriaDistributionService $criteriaDistributionService */
        $criteriaDistributionService = $this->get('distribution.criteria_distribution_service');
        try
        {
            $receivers = $criteriaDistributionService->load($request->request->all());
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage(), 500);
        }

        $json = $this->get('jms_serializer')
            ->serialize($receivers, 'json');

        return new Response($json);
    }
}