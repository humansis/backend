<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use NewApiBundle\Pagination\Paginator;
use NewApiBundle\Enum\AssistanceTargetType;
use NewApiBundle\Enum\AssistanceType;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Assistance\Enum\CommodityDivision;
use NewApiBundle\InputType\AssistanceTargetFilterInputType;
use NewApiBundle\InputType\AssistanceTypeFilterInputType;
use NewApiBundle\Utils\SectorService;
use NewApiBundle\Services\CodeListService;
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

    /** @var CodeListService */
    private $codeListService;

    /**
     * AssistanceCodelistController constructor.
     * @param SectorService $sectorService
     * @param CodeListService $codeListService
     */
    public function __construct(
        SectorService $sectorService,
        CodeListService $codeListService
    )
    {
        $this->sectorService = $sectorService;
        $this->codeListService = $codeListService;
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

        $targets = $this->codeListService->mapEnum($data);

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

        $assistanceTypes = $this->codeListService->mapEnum($data);

        return $this->json(new Paginator($assistanceTypes));
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/commodity/divisions")
     *
     * @return JsonResponse
     */
    public function getCommodityDivision(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(CommodityDivision::values());

        return $this->json(new Paginator($data));
    }
}
