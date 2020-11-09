<?php

namespace NewApiBundle\Controller;

use DistributionBundle\Entity\Assistance;
use DistributionBundle\Repository\AssistanceRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Mapper\AssistanceMapper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

//TODO This is draft how AssistanceController could look
class AssistanceController extends Controller
{
    /** @var AssistanceMapper */
    private $assistanceMapper;

    public function __construct(AssistanceMapper $mapper)
    {
        $this->assistanceMapper = $mapper;
    }

    /**
     * @Rest\Get("/project/{projectId}/assistances", requirements={"projectId" = "\d+"})
     *
     * @param int $projectId
     *
     * @return JsonResponse
     */
    public function getProjectAssistances(int $projectId): JsonResponse
    {
        /** @var AssistanceRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Assistance::class);

        return $this->json($this->assistanceMapper->toFullArrays($repository->getAllByProjectId($projectId)));
    }
}
