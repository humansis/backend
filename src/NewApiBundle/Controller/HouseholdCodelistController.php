<?php

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\HouseholdLocation;
use CommonBundle\Pagination\Paginator;
use NewApiBundle\Utils\CodeLists;
use ProjectBundle\Enum\Livelihood;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;

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
}
