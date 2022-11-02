<?php

declare(strict_types=1);

namespace Controller;

use Pagination\Paginator;
use Enum\AssistanceTargetType;
use Enum\AssistanceType;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\Assistance\Enum\CommodityDivision;
use InputType\AssistanceTargetFilterInputType;
use InputType\AssistanceTypeFilterInputType;
use Utils\SectorService;
use Services\CodeListService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Cache(expires="+12 hours", public=true)
 */
class AssistanceCodelistController extends AbstractController
{
    /**
     * AssistanceCodelistController constructor.
     */
    public function __construct(private readonly SectorService $sectorService, private readonly CodeListService $codeListService)
    {
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/targets")
     *
     *
     */
    public function getTargets(AssistanceTargetFilterInputType $targetTypeFilterType): JsonResponse
    {
        if (!$targetTypeFilterType->hasType()) {
            $data = AssistanceTargetType::values();
        } else {
            $data = $this->sectorService->findTargetsByType($targetTypeFilterType->getType());
        }

        $targets = $this->codeListService->mapEnum($data);

        return $this->json(new Paginator($targets));
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/types")
     */
    public function getTypes(AssistanceTypeFilterInputType $typeSubsectorInputType): JsonResponse
    {
        if (!$typeSubsectorInputType->hasSubsector()) {
            $data = AssistanceType::values();
        } else {
            $sector = $this->sectorService->findBySubSector($typeSubsectorInputType->getSubsector());
            if (is_null($sector)) {
                $data = [];
            } else {
                $fn = fn($value) => $sector->isAssistanceTypeAllowed($value);

                $data = array_filter(AssistanceType::values(), $fn);
            }
        }

        $assistanceTypes = $this->codeListService->mapEnum($data);

        return $this->json(new Paginator($assistanceTypes));
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/commodity/divisions")
     */
    public function getCommodityDivision(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(CommodityDivision::values());

        return $this->json(new Paginator($data));
    }
}
