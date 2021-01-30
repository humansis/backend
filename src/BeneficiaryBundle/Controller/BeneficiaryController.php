<?php

namespace BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Enum\ResidencyStatus;
use BeneficiaryBundle\Utils\BeneficiaryService;

use CommonBundle\Pagination\Paginator;
use DistributionBundle\Enum\AssistanceTargetType;
use DistributionBundle\Utils\CriteriaAssistanceService;
use Exception;
use NewApiBundle\Utils\CodeLists;
use ProjectBundle\Entity\Project;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use VoucherBundle\Entity\Smartcard;

class BeneficiaryController extends Controller
{
    /** @var BeneficiaryService */
    private $beneficiaryService;
    /** @var SerializerInterface */
    private $serializer;
    /** @var CriteriaAssistanceService */
    private $criteriaAssistanceService;
    /** @var LoggerInterface */
    private $logger;

    /**
     * BeneficiaryController constructor.
     *
     * @param BeneficiaryService        $beneficiaryService
     * @param SerializerInterface       $serializer
     * @param CriteriaAssistanceService $criteriaAssistanceService
     * @param LoggerInterface           $logger
     */
    public function __construct(BeneficiaryService $beneficiaryService, SerializerInterface $serializer,
                                CriteriaAssistanceService $criteriaAssistanceService,
                                LoggerInterface $logger
    )
    {
        $this->beneficiaryService = $beneficiaryService;
        $this->serializer = $serializer;
        $this->criteriaAssistanceService = $criteriaAssistanceService;
        $this->logger = $logger;
    }

    /**
     * @Rest\Get("/beneficiaries/residency-statuses")
     *
     * @return JsonResponse
     */
    public function getResidencyStatuses(): JsonResponse
    {
        $data = CodeLists::mapEnum(ResidencyStatus::all());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/beneficiaries/vulnerability-criterias")
     *
     * @return JsonResponse
     */
    public function getVulnerabilityCriterion(): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $criterion = $em->getRepository(VulnerabilityCriterion::class)
            ->findAllActive();

        return $this->json(new Paginator(CodeLists::mapCriterion($criterion)));
    }

    /**
     * @Rest\Get("/vulnerability_criteria", name="get_all_vulnerability_criteria")
     * @SWG\Tag(name="Beneficiary")
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @return Response
     */
    public function getAllVulnerabilityCriteriaAction()
    {
        $vulnerabilityCriteria = $this->beneficiaryService->getAllVulnerabilityCriteria();
        $json = $this->serializer
            ->serialize($vulnerabilityCriteria, 'json');

        return new Response($json);
    }

    /**
     * @Rest\Post("/beneficiaries/project/{id}")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE', project) or is_granted('ROLE_DISTRIBUTION_CREATE')")
     *
     * @SWG\Tag(name="CriteriaDistributions")
     * @SWG\Tag(name="Beneficiary")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     required=true,
     *     schema={}
     * )
     *
     * @SWG\Parameter(
     *     name="limit",
     *     in="query",
     *     type="integer"
     * )
     *
     * @SWG\Parameter(
     *     name="offset",
     *     in="query",
     *     type="integer"
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
    public function getBeneficiariesAction(Request $request, Project $project)
    {
        $filters = $request->request->all();
        $filters['countryIso3'] = $filters['__country'];
        $threshold = $filters['threshold'];
        $targetType = $filters['target_type'] ?? null;

        if (!in_array($targetType, AssistanceTargetType::values())) {
            throw new BadRequestHttpException('Nonexistent assistance target type: '.$targetType);
        }

        $limit = $request->query->getInt('limit', 1000);
        $offset = $request->query->getInt('offset', 0);

        $data = $this->criteriaAssistanceService->getList($filters, $project, $targetType, $threshold, $limit, $offset);

        return $this->json($data);
    }

    /**
     * @Rest\Post("/beneficiaries/project/{id}/number")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE', project) or is_granted('ROLE_DISTRIBUTION_CREATE')")
     *
     * @SWG\Tag(name="CriteriaDistributions")
     * @SWG\Tag(name="Beneficiary")
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
        $sector = $filters['sector'];
        $subSector = $filters['subsector'];
        $targetType = $filters['target_type'] ?? null;

        if (!in_array($targetType, AssistanceTargetType::values())) {
            throw new BadRequestHttpException('Nonexistent assistance target type: '.$targetType);
        }
        $receivers = $this->criteriaAssistanceService->load($filters, $project, $targetType, $sector, $subSector, $threshold, true);

        return $this->json($receivers);
    }

     /**
     * Edit a beneficiary {id} with data in the body
     *
     * @Rest\Post("/beneficiaries/{id}", name="update_beneficiary")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     * @SWG\Tag(name="Beneficiaries")
     *
     * @SWG\Parameter(
     *     name="beneficiary",
     *     in="body",
     *     type="string",
     *     required=true,
     *     description="fields of the beneficiary which must be updated",
     *     @Model(type=Beneficiary::class)
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="SUCCESS",
     *     @Model(type=Beneficiary::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @param Beneficiary $beneficiary
     * @return Response
     */
    public function updateAction(Request $request, Beneficiary $beneficiary)
    {
        $this->logger->error('beneficiary', [$beneficiary->getId()]);
        $this->logger->error('headers', $request->headers->all());
        $this->logger->error('content', [$request->getContent()]);

        $beneficiaryData = $request->request->all();

        try {
            $newBeneficiary = $this->beneficiaryService->update($beneficiary, $beneficiaryData);
        } catch (Exception $exception) {
            $this->logger->error('exception', [$exception->getMessage()]);
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        $json = $this->serializer
        ->serialize(
                $newBeneficiary,
                'json', ['groups' => ['FullBeneficiary'], 'datetime_format' => 'd-m-Y H:i:s']);
        return new Response($json);
    }

    /**
     * Edit a beneficiary {id} with data in the body.
     *
     * @Rest\Post("/offline-app/v1/beneficiaries/{id}")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Offline App")
     * @SWG\Tag(name="Beneficiaries")
     *
     * @SWG\Parameter(
     *     name="beneficiary",
     *     in="body",
     *     type="string",
     *     required=true,
     *     description="fields of the beneficiary which must be updated",
     *     @Model(type=Beneficiary::class)
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="SUCCESS",
     *     @Model(type=Beneficiary::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @param Beneficiary $beneficiary
     * @return Response
     */
    public function offlineUpdateAction(Request $request, Beneficiary $beneficiary)
    {
        return $this->updateAction($request, $beneficiary);
    }

    /**
     * @Rest\Get("/beneficiaries/{id}", name="get_one_beneficiary", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Beneficiaries")
     *
     * @SWG\Response(
     *     response=200,
     *     description="one beneficiary",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Beneficiary::class))
     *     )
     * )
     *
     * @param Beneficiary $Beneficiary
     *
     * @return Response
     */
    public function getOneAction(Beneficiary $Beneficiary)
    {
        if (true === $Beneficiary->getArchived()) {
            return new Response("Beneficiary was archived", Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer
        ->serialize(
            $Beneficiary,
            'json',
            ['groups' => ['FullBeneficiary'], 'datetime_format' => 'd-m-Y H:i:s']
        );
        return new Response($json);
    }

    /**
     * Beneficiary by its smartcard.
     *
     * @Rest\Get("/offline-app/v1/smartcards/{serialNumber}/beneficiary")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     * @ParamConverter("smartcard")
     *
     * @SWG\Tag(name="Smartcards")
     * @SWG\Tag(name="Offline App")
     *
     * @SWG\Parameter(
     *     name="serialNumber",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="Serial number (GUID) of smartcard"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Beneficiary - owner of given smartcard",
     *     @Model(type=Beneficiary::class, groups={"FullBeneficiary"})
     * )
     *
     * @SWG\Response(response=404, description="Smartcard does not exists.")
     *
     * @param Smartcard $smartcard
     *
     * @return Response
     */
    public function beneficiary(Smartcard $smartcard): Response
    {
        $json = $this->serializer
            ->serialize($smartcard->getBeneficiary(), 'json', ['groups' => ['FullBeneficiary'], 'datetime_format' => 'd-m-Y H:i:s']);

        return new Response($json);
    }
}
