<?php

namespace NewApiBundle\Controller;

use DistributionBundle\Entity\Assistance;
use DistributionBundle\Repository\AssistanceRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use ProjectBundle\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class AssistanceController extends Controller
{
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

        $assistances = $repository->getAllByProject($project);

        return $this->json($assistances);
    }
}
