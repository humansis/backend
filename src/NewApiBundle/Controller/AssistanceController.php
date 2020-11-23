<?php

namespace NewApiBundle\Controller;

use DistributionBundle\Entity\Assistance;
use DistributionBundle\Repository\AssistanceRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\AssistanceOrderInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\JsonResponse;

class AssistanceController extends AbstractController
{
    /**
     * @Rest\Get("/project/{id}/assistances")
     *
     * @param Project                  $project
     * @param Pagination               $pagination
     * @param AssistanceOrderInputType $orderBy
     *
     * @return JsonResponse
     */
    public function getProjectAssistances(Project $project, Pagination $pagination, AssistanceOrderInputType $orderBy): JsonResponse
    {
        /** @var AssistanceRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Assistance::class);

        $assistances = $repository->findByProject($project, $orderBy, $pagination);

        return $this->json($assistances);
    }
}
