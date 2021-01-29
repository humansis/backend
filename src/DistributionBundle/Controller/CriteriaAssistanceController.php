<?php


namespace DistributionBundle\Controller;

use DistributionBundle\Mapper\CampMapper;
use DistributionBundle\Utils\CriteriaAssistanceService;

use ProjectBundle\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Serializer\SerializerInterface;

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
    /** @var CriteriaAssistanceService */
    private $criteriaAssistanceService;
    /** @var SerializerInterface */
    private $serializer;
    /** @var CampMapper */
    private $campMapper;

    /**
     * CriteriaAssistanceController constructor.
     *
     * @param CriteriaAssistanceService $criteriaAssistanceService
     * @param SerializerInterface       $serializer
     * @param CampMapper                $campMapper
     */
    public function __construct(CriteriaAssistanceService $criteriaAssistanceService, SerializerInterface $serializer, CampMapper $campMapper)
    {
        $this->criteriaAssistanceService = $criteriaAssistanceService;
        $this->serializer = $serializer;
        $this->campMapper = $campMapper;
    }

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
        $filters = $request->request->all();
        $countryISO3 = $filters['__country'];
        $criteria = $this->criteriaAssistanceService->getAll($countryISO3);

        $json = $this->serializer
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
            $camps = $this->criteriaAssistanceService->getCamps($countryIso3);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($this->campMapper->toArrays($camps));
    }
}
