<?php


namespace DistributionBundle\Controller;

use DistributionBundle\Utils\CriteriaDistributionService;
use JMS\Serializer\SerializationContext;
use ProjectBundle\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class CriteriaDistributionController
 * @package DistributionBundle\Controller
 */
class CriteriaDistributionController extends Controller
{

    /**
     * @Rest\Get("/distributions/criteria", name="get_criteria_celection")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="CriteriaDistributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Return the list of distribution criteria",
     *      examples={
     *          "application/json":
     *          {
     *             {
     *              "field_string": "gender",
     *              "type": "boolean"
     *             },
     *             {
     *              "field_string": "dateOfBirth",
     *              "type": "date"
     *             },
     *             {
     *              "table_string": "vulnerabilityCriteria",
     *              "id": 1,
     *              "field_string": "disabled"
     *             },
     *             {
     *              "table_string": "vulnerabilityCriteria",
     *              "id": 2,
     *              "field_string": "soloParent"
     *             },
     *             {
     *              "table_string": "countrySpecific",
     *              "id": 1,
     *              "field_string": "IDPoor",
     *              "type": "Number"
     *             },
     *             {
     *              "table_string": "countrySpecific",
     *              "id": 2,
     *              "field_string": "equityCardNo",
     *               "type": "Text"
     *             }
     *           }
     *     }
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function getCriteriaAction(Request $request)
    {
        /** @var CriteriaDistributionService $criteriaDistributionService */
        $criteriaDistributionService = $this->get('distribution.criteria_distribution_service');
        $filters = $request->request->all();
        $countryISO3 = $filters['__country'];
        $criteria = $criteriaDistributionService->getAll($countryISO3);

        $json = $this->get('jms_serializer')
            ->serialize(
                $criteria,
                'json',
                SerializationContext::create()->setSerializeNull(true)->setGroups(["Criteria"])
            );
        return new Response($json);
    }

    /**
     * @Rest\Post("/distributions/criteria/project/{id}/number")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE', project)")
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
     * @param Project $project
     * @return Response
     */
    public function getBeneficiariesNumberAction(Request $request, Project $project)
    {
        $filters = $request->request->all();
        $filters['countryIso3'] = $filters['__country'];
        $threshold = $filters['threshold'];

        /** @var CriteriaDistributionService $criteriaDistributionService */
        try {
            $criteriaDistributionService = $this->get('distribution.criteria_distribution_service');
            $receivers = $criteriaDistributionService->load($filters, $project, $threshold, true);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $json = $this->get('jms_serializer')
            ->serialize(
                $receivers,
                'json'
            );

        return new Response($json);
    }

     /**
     * @Rest\Get("/camps")
     *
     * @SWG\Tag(name="CriteriaDistributionsCamps")
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
     */
    public function getCamps(Request $request)
    {
        $data = $request->request->all();
        $countryIso3 = $data['__country'];

        /** @var CriteriaDistributionService $criteriaDistributionService */
        try {
            $criteriaDistributionService = $this->get('distribution.criteria_distribution_service');
            $camps = $criteriaDistributionService->getCamps($countryIso3);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $json = $this->get('jms_serializer')
            ->serialize(
                $camps,
                'json'
            );

        return new Response($json);
    }
}
