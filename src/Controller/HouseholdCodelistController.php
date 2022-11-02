<?php

declare(strict_types=1);

namespace Controller;

use Entity\HouseholdLocation;
use Entity\Referral;
use Pagination\Paginator;
use Enum\HouseholdAssets;
use Enum\HouseholdShelterStatus;
use Enum\HouseholdSupportReceivedType;
use Enum\Livelihood;
use Services\CodeListService;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Cache(expires="+5 days", public=true)
 */
class HouseholdCodelistController extends AbstractController
{
    public function __construct(private readonly CodeListService $codeListService)
    {
    }

    /**
     * @Rest\Get("/web-app/v1/households/livelihoods")
     */
    public function getLivelihoods(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(Livelihood::values());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/households/assets")
     */
    public function getAssets(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(HouseholdAssets::values());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/households/support-received-types")
     */
    public function supportReceivedTypes(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(HouseholdSupportReceivedType::values());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/households/shelter-statuses")
     */
    public function getShelterStatuses(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(HouseholdShelterStatus::values());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/households/locations/types")
     */
    public function getLocationTypes(): JsonResponse
    {
        $data = $this->codeListService->mapArray(HouseholdLocation::LOCATION_TYPES);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/households/referrals/types")
     */
    public function referralTypes(): JsonResponse
    {
        $data = $this->codeListService->mapArray(Referral::REFERRALTYPES);

        return $this->json(new Paginator($data));
    }
}
