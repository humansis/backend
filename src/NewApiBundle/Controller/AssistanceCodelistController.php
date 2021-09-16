<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use DistributionBundle\Enum\AssistanceTargetType;
use DistributionBundle\Enum\AssistanceType;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Codelist\CodeLists;
use NewApiBundle\InputType\AssistanceTargetFilterInputType;
use NewApiBundle\InputType\AssistanceTypeFilterInputType;
use ProjectBundle\Utils\SectorService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Cache(expires="+5 days", public=true)
 */
class AssistanceCodelistController extends AbstractController
{
    /**
     * @var SectorService
     */
    private $sectorService;

    /**
     * AssistanceCodelistController constructor.
     * @param SectorService $sectorService
     */
    public function __construct(
        SectorService $sectorService
    )
    {
        $this->sectorService = $sectorService;
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/targets")
     *
     * @param AssistanceTargetFilterInputType $targetTypeFilterType
     *
     * @return JsonResponse
     */
    public function getTargets(AssistanceTargetFilterInputType $targetTypeFilterType): JsonResponse
    {
        if (!$targetTypeFilterType->hasType()) {
            $data = AssistanceTargetType::values();
        } else {
            $data = $this->sectorService->findTargetsByType($targetTypeFilterType->getType());
        }

        $targets = CodeLists::mapEnum($data);

        return $this->json(new Paginator($targets));
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/types")
     *
     * @param AssistanceTypeFilterInputType $typeSubsectorInputType
     * @return JsonResponse
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
                $fn = function ($value) use ($sector) {
                    return $sector->isAssistanceTypeAllowed($value);
                };

                $data = array_filter(AssistanceType::values(), $fn);
            }
        }

        $assistanceTypes = CodeLists::mapEnum($data);

        return $this->json(new Paginator($assistanceTypes));
    }
}
