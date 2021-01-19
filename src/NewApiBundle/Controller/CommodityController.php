<?php

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use DistributionBundle\Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;

class CommodityController extends AbstractController
{
    /**
     * @Rest\Get("/assistances/{id}/commodities")
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
