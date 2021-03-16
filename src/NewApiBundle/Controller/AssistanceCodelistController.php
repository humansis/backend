<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use DistributionBundle\DBAL\AssistanceTypeEnum;
use DistributionBundle\Enum\AssistanceTargetType;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\TargetTypeFilterType;
use NewApiBundle\InputType\TypeSubsectorInputType;
use NewApiBundle\Utils\CodeLists;
use ProjectBundle\Utils\SectorService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
     * @Rest\Get("/assistances/targets")
     *
     * @param TargetTypeFilterType $targetTypeFilterType
     * @return JsonResponse
     */
    public function getTargets(TargetTypeFilterType $targetTypeFilterType): JsonResponse
    {
        if (!$targetTypeFilterType->hasType()) {
            $data = CodeLists::mapArray(AssistanceTargetType::values());

            return $this->json(new Paginator($data));
        }

        $targets = $this->sectorService->findTargetsByType($targetTypeFilterType->getType());

        return $this->json(new Paginator($targets));
    }

    /**
     * @Rest\Get("/assistances/types")
     *
     * @param TypeSubsectorInputType $typeSubsectorInputType
     * @return JsonResponse
     */
    public function getTypes(TypeSubsectorInputType $typeSubsectorInputType): JsonResponse
    {
        if (!$typeSubsectorInputType->hasType()) {
            $data = AssistanceTypeEnum::all();

            $this->json(new Paginator($data));
        }

        $sector = $this->sectorService->findBySubSector($typeSubsectorInputType->getType());

        if (is_null($sector)) {
            throw new BadRequestHttpException('Provided sector for provided subsector does not exist');
        }

        $fn = function ($value) use ($sector) {
            return $sector->isAssistanceTypeAllowed($value);
        };

        $assistanceTypes = array_filter(AssistanceTypeEnum::all(), $fn);

        return $this->json(new Paginator($assistanceTypes));
    }
}
