<?php


namespace DistributionBundle\Controller;

use DistributionBundle\Utils\CriteriaAssistanceService;

use NewApiBundle\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class CriteriaAssistanceController
 * @package DistributionBundle\Controller
 *
 * @SWG\Parameter(
 *     name="country",
 *     in="header",
 *     type="string",
 *     required=true
 * )
 */
class CriteriaAssistanceController extends Controller
{

    /**
     * @Rest\Get("/distributions/criteria", name="get_criteria_celection")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE') or is_granted('ROLE_DISTRIBUTION_CREATE')")
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
        /** @var CriteriaAssistanceService $criteriaAssistanceService */
        $criteriaAssistanceService = $this->get('distribution.criteria_assistance_service');
        $filters = $request->request->all();
        $countryISO3 = $filters['__country'];
        $criteria = $criteriaAssistanceService->getAll($countryISO3);

        $json = $this->get('serializer')
            ->serialize(
                $criteria,
                'json',
                ['groups' => ["Criteria"]]
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
     * @deprecated Use `/beneficiaries/project/{id}/number` instead
     */
    public function getBeneficiariesNumberAction(Request $request, Project $project)
    {
        return $this->forward('BeneficiaryBundle:Beneficiary:getBeneficiariesNumber', ['request' => $request, 'project' => $project]);
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

        /** @var CriteriaAssistanceService $criteriaAssistanceService */
        try {
            $criteriaAssistanceService = $this->get('distribution.criteria_assistance_service');
            $camps = $criteriaAssistanceService->getCamps($countryIso3);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($this->get(\DistributionBundle\Mapper\CampMapper::class)->toArrays($camps));
    }
}
