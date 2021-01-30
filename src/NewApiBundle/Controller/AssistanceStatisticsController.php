<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use DistributionBundle\Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Entity\AssistanceStatistics;
use NewApiBundle\InputType\AssistanceStatisticsFilterInputType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AssistanceStatisticsController extends AbstractController
{
    /**
     * @Rest\Get("/assistances/{id}/statistics")
     *
     * @param Assistance $assistance
     *
     * @return JsonResponse
     */
    public function assistanceStatistics(Assistance $assistance): JsonResponse
    {
        $statistics = $this->getDoctrine()->getRepository(AssistanceStatistics::class)->findByAssistance($assistance);

        return $this->json($statistics);
    }

    /**
     * @Rest\Get("/assistances/statistics")
     *
     * @param Request                             $request
     * @param AssistanceStatisticsFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function getProjectAssistances(Request $request, AssistanceStatisticsFilterInputType $filter): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $statistics = $this->getDoctrine()->getRepository(AssistanceStatistics::class)->findByParams($countryIso3, $filter);

        return $this->json(new Paginator($statistics));
    }
}
