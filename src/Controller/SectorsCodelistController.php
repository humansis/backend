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
    public function __construct(private readonly SectorService $sectorService, private readonly CodeListService $codeListService)
    {
    }

    /**
     * @Rest\Get("/web-app/v1/sectors")
     *
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
     *
     */
    public function getSectorsV2(Project $project): JsonResponse
    {
        $data = $this->sectorService->getSectorsInProject($project);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/sectors/{code}/subsectors")
     *
     *
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
