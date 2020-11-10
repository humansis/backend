<?php

namespace NewApiBundle\Controller;

use DistributionBundle\Entity\Assistance;
use DistributionBundle\Repository\AssistanceRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Mapper\AssistanceMapper;
use ProjectBundle\Entity\Project;
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
     * @Rest\Get("/project/{id}/assistances")
     *
     * @param Project $project
     *
     * @return JsonResponse
     */
    public function getProjectAssistances(Project $project): JsonResponse
    {
        /** @var AssistanceRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Assistance::class);

        return $this->json($this->assistanceMapper->toFullArrays($repository->getAllByProject($project)));
    }
}
