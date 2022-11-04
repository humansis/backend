<?php

declare(strict_types=1);

namespace Controller;

use Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use DBAL\SectorEnum;
use Entity\Project;
use Services\CodeListService;
use Utils\SectorService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Cache(expires="+12 hours", public=true)
 */
class SectorsCodelistController extends AbstractController
{
    /** @var SectorService */
    private $sectorService;

    /** @var CodeListService */
    private $codeListService;

    public function __construct(
        SectorService $sectorService,
        CodeListService $codeListService
    ) {
        $this->sectorService = $sectorService;
        $this->codeListService = $codeListService;
    }

    /**
     * @Rest\Get("/web-app/v1/sectors")
     *
     * @return JsonResponse
     * @deprecated use /projects/{id}/sectors instead
     */
    public function getSectors(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(SectorEnum::all());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v2/projects/{id}/sectors")
     *
     * @param Project $project
     *
     * @return JsonResponse
     */
    public function getSectorsV2(Project $project): JsonResponse
    {
        $data = $this->sectorService->getSectorsInProject($project);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/sectors/{code}/subsectors")
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

        $subSectors = $this->codeListService->mapSubSectors($this->sectorService->findSubsSectorsBySector($code));

        return $this->json(new Paginator($subSectors));
    }
}
