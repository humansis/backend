<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use DistributionBundle\Entity\Assistance;
use DistributionBundle\Repository\AssistanceRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\AssistanceCreateInputType;
use NewApiBundle\InputType\AssistanceFilterInputType;
use NewApiBundle\InputType\AssistanceOrderInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AssistanceController extends AbstractController
{
    /**
     * @Rest\Get("/assistances")
     *
     * @param Request                   $request
     * @param AssistanceFilterInputType $filter
     * @param Pagination                $pagination
     * @param AssistanceOrderInputType  $orderBy
     *
     * @return JsonResponse
     */
    public function assistances(
        Request $request,
        AssistanceFilterInputType $filter,
        Pagination $pagination,
        AssistanceOrderInputType $orderBy
    ): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $assistances = $this->getDoctrine()->getRepository(Assistance::class)->findByParams(null, $countryIso3, $filter, $orderBy, $pagination);

        return $this->json($assistances);
    }

    /**
     * @Rest\Post("/assistances")
     *
     * @param AssistanceCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(AssistanceCreateInputType $inputType): JsonResponse
    {
        $assistance = $this->get('distribution.assistance_service')->create($inputType);

        return $this->json($assistance);
    }

    /**
     * @Rest\Get("/assistances/{id}")
     *
     * @param Assistance $assistance
     *
     * @return JsonResponse
     */
    public function item(Assistance $assistance): JsonResponse
    {
        if ($assistance->getArchived()) {
            $this->createNotFoundException();
        }

        return $this->json($assistance);
    }

    /**
     * @Rest\Patch("/assistances/{id}")
     *
     * @param Assistance $assistance
     *
     * @return JsonResponse
     */
    public function update(Request $request, Assistance $assistance): JsonResponse
    {
        if ($request->request->get('validated', false)) {
            $this->get('distribution.assistance_service')->validateDistribution($assistance);
        }

        if ($request->request->get('completed', false)) {
            $this->get('distribution.assistance_service')->complete($assistance);
        }

        return $this->json($assistance);
    }

    /**
     * @Rest\Delete("/assistances/{id}")
     *
     * @param Assistance $assistance
     *
     * @return JsonResponse
     */
    public function delete(Assistance $assistance): JsonResponse
    {
        $this->get('distribution.assistance_service')->delete($assistance);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Post("/assistances/summaries")
     *
     * @param AssistanceCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function summaries(AssistanceCreateInputType $inputType): JsonResponse
    {
        $number = $this->get('distribution.criteria_assistance_service')->count($inputType);

        return $this->json(['number' => (int) $number]);
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
