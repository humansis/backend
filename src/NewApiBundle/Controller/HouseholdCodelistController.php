<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use NewApiBundle\Entity\Household;
use NewApiBundle\Entity\HouseholdLocation;
use NewApiBundle\Entity\Referral;
use BeneficiaryBundle\Entity\HouseholdLocation;
use BeneficiaryBundle\Entity\Referral;
use CommonBundle\Pagination\Paginator;
use NewApiBundle\Enum\Domain;
use NewApiBundle\Enum\HouseholdAssets;
use NewApiBundle\Enum\HouseholdShelterStatus;
use NewApiBundle\Enum\HouseholdSupportReceivedType;
use NewApiBundle\Enum\Livelihood;
use NewApiBundle\Services\CodeListService;
use ProjectBundle\Enum\Livelihood;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Cache(expires="+5 days", public=true)
 */
class HouseholdCodelistController extends AbstractController
{
    /** @var CodeListService */
    private $codeListService;

    public function __construct(CodeListService $codeListService)
    {
        $this->codeListService = $codeListService;
    }
    
    /**
     * @Rest\Get("/web-app/v1/households/livelihoods")
     *
     * @return JsonResponse
     */
    public function getLivelihoods(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(Livelihood::values(), Domain::ENUMS);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/households/assets")
     *
     * @return JsonResponse
     */
    public function getAssets(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(HouseholdAssets::values());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/households/support-received-types")
     *
     * @return JsonResponse
     */
    public function supportReceivedTypes(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(HouseholdSupportReceivedType::values());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/households/shelter-statuses")
     *
     * @return JsonResponse
     */
    public function getShelterStatuses(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(HouseholdShelterStatus::values());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/households/locations/types")
     *
     * @return JsonResponse
     */
    public function getLocationTypes(): JsonResponse
    {
        $data = $this->codeListService->mapArray(HouseholdLocation::LOCATION_TYPES, Domain::ENUMS);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/households/referrals/types")
     *
     * @return JsonResponse
     */
    public function referralTypes(): JsonResponse
    {
        $data = $this->codeListService->mapArray(Referral::REFERRALTYPES, Domain::SECTORS);

        return $this->json(new Paginator($data));
    }
}
