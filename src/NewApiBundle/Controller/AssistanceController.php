<?php

namespace NewApiBundle\Controller;

use DistributionBundle\Entity\Assistance;
use DistributionBundle\Repository\AssistanceRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\AssistanceOrderInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AssistanceController extends AbstractController
{
    /**
     * @Rest\Get("/assistances")
     *
     * @param Request                  $request
     * @param Pagination               $pagination
     * @param AssistanceOrderInputType $orderBy
     *
     * @return JsonResponse
     */
    public function assistances(Request $request, Pagination $pagination, AssistanceOrderInputType $orderBy): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $upcoming = ($request->query->has('upcoming') && $request->query->getBoolean('upcoming'));

        $assistances = $this->getDoctrine()->getRepository(Assistance::class)->findByParams(null, $countryIso3, $upcoming, $orderBy, $pagination);

        return $this->json($assistances);
    }

    /**
     * @Rest\Get("/projects/{id}/assistances")
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

        $assistances = $repository->findByParams($project, null, null, $orderBy, $pagination);

        return $this->json($assistances);
    }
}
