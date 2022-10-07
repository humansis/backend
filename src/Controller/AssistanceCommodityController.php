<?php

namespace Controller;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Exception\CsvParserException;
use Pagination\Paginator;
use Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\Assistance\AssistanceFactory;
use InputType\AssistanceCreateInputType;
use InputType\CommodityFilterInputType;
use Repository\CommodityRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

class AssistanceCommodityController extends AbstractController
{
    /**
     * @Rest\Get("/web-app/v1/assistances/commodities")
     *
     * @param CommodityFilterInputType $filter
     * @param CommodityRepository $commodityRepository
     *
     * @return JsonResponse
     */
    public function commodities(
        CommodityFilterInputType $filter,
        CommodityRepository $commodityRepository
    ): JsonResponse {
        $projects = $commodityRepository->findByParams($filter);

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

    /**
     * @Rest\Post("/web-app/v1/assistances/commodities")
     *
     * @param AssistanceCreateInputType $inputType
     * @param AssistanceFactory $factory
     *
     * @return JsonResponse
     * @throws CsvParserException
     * @throws EntityNotFoundException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    public function create(AssistanceCreateInputType $inputType, AssistanceFactory $factory): JsonResponse
    {
        return $this->json(new Paginator($factory->create($inputType)->getCommoditiesSummary()));
    }
}
