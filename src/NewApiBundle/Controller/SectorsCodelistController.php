<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Codelist\CodeLists;
use ProjectBundle\DBAL\SectorEnum;
use ProjectBundle\Entity\Project;
use ProjectBundle\Utils\SectorService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Cache(expires="+5 days", public=true)
 */
class SectorsCodelistController extends AbstractController
{
    /** @var SectorService */
    private $sectorService;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(SectorService $sectorService, TranslatorInterface $translator)
    {
        $this->sectorService = $sectorService;
        $this->translator = $translator;
    }

    /**
     * @Rest\Get("/web-app/v1/sectors")
     *
     * @return JsonResponse
     * @deprecated use /projects/{id}/sectors instead
     */
    public function getSectors(): JsonResponse
    {
        $data = CodeLists::mapEnum(SectorEnum::all(), $this->translator, 'sectors');

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

        $subSectors = CodeLists::mapSubSectors($this->sectorService->findSubsSectorsBySector($code));

        return $this->json(new Paginator($subSectors));
    }
}
