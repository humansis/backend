<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Enum\Domain;
use NewApiBundle\Services\CodeListService;
use ProjectBundle\DBAL\SectorEnum;
use ProjectBundle\Entity\Project;
use ProjectBundle\Utils\SectorService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Cache(expires="+5 days", public=true)
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
        $data = $this->codeListService->mapEnum(SectorEnum::all(), Domain::SECTORS);

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

        $subSectors = $this->codeListService->mapSubSectors($this->sectorService->findSubsSectorsBySector($code), Domain::SECTORS);

        return $this->json(new Paginator($subSectors));
    }
}
