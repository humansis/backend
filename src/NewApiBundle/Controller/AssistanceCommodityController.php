<?php

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\Commodity;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\CommodityFilterInputType;
use Symfony\Component\HttpFoundation\JsonResponse;

class AssistanceCommodityController extends AbstractController
{
    /**
     * @Rest\Get("/web-app/v1/assistances/commodities")
     *
     * @param CommodityFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function commodities(CommodityFilterInputType $filter): JsonResponse
    {
        $projects = $this->getDoctrine()->getRepository(Commodity::class)->findByParams($filter);

        return $this->json($projects);
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/commodities")
     *
     * @param Assistance $assistance
     *
     * @return JsonResponse
     */
    public function commoditiesByAssistance(Assistance $assistance): JsonResponse
    {
        if ($assistance->getArchived()) {
            throw $this->createNotFoundException();
        }

        return $this->json(new Paginator($assistance->getCommodities()));
    }
}
