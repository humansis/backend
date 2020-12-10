<?php

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Utils\CodeLists;
use ProjectBundle\DBAL\SectorEnum;
use ProjectBundle\Utils\SectorService;
use Symfony\Component\HttpFoundation\JsonResponse;

class SectorsCodelistController extends AbstractController
{
    /** @var SectorService */
    private $sectorService;

    public function __construct(SectorService $sectorService)
    {
        $this->sectorService = $sectorService;
    }

    /**
     * @Rest\Get("/sectors")
     *
     * @return JsonResponse
     */
    public function getSectors(): JsonResponse
    {
        $data = CodeLists::mapEnum(SectorEnum::all());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/sectors/{code}/subsectors")
     *
     * @param string $code
     *
     * @return JsonResponse
     */
    public function getSubSectors(string $code): JsonResponse
    {
        if (!in_array($code, SectorEnum::all())) {
            throw $this->createNotFoundException('Sector not found');
        }

        $subSectors = CodeLists::mapSubSectors($this->sectorService->findSubsSectorsBySector($code));

        return $this->json(new Paginator($subSectors));
    }
}
