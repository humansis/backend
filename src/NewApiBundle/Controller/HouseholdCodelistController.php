<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\HouseholdLocation;
use BeneficiaryBundle\Entity\Referral;
use CommonBundle\Pagination\Paginator;
use NewApiBundle\Utils\CodeLists;
use ProjectBundle\Enum\Livelihood;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Cache(expires="+5 days", public=true)
 */
class HouseholdCodelistController extends AbstractController
{
    /**
     * @Rest\Get("/households/livelihoods")
     *
     * @return JsonResponse
     */
    public function getLivelihoods(): JsonResponse
    {
        $data = [];
        foreach (Livelihood::values() as $code) {
            $data[] = ['code' => $code, 'value' => Livelihood::translate($code)];
        }

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/households/assets")
     *
     * @return JsonResponse
     */
    public function getAssets(): JsonResponse
    {
        $data = [];
        foreach (Household::ASSETS as $key => $value) {
            $data[] = ['code' => $key, 'value' => $value];
        }

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/households/support-received-types")
     *
     * @return JsonResponse
     */
    public function supportReceivedTypes(): JsonResponse
    {
        $data = CodeLists::mapArray(Household::SUPPORT_RECIEVED_TYPES);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/households/shelter-statuses")
     *
     * @return JsonResponse
     */
    public function getShelterStatuses(): JsonResponse
    {
        $data = CodeLists::mapArray(Household::SHELTER_STATUSES);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/households/locations/types")
     *
     * @return JsonResponse
     */
    public function getLocationTypes(): JsonResponse
    {
        $data = CodeLists::mapEnum(HouseholdLocation::LOCATION_TYPES);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/households/referrals/types")
     *
     * @return JsonResponse
     */
    public function referralTypes(): JsonResponse
    {
        $data = CodeLists::mapArray(Referral::REFERRALTYPES);

        return $this->json(new Paginator($data));
    }
}
