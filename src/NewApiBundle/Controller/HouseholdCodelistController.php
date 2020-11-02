<?php

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Household;
use CommonBundle\Pagination\Paginator;
use ProjectBundle\Enum\Livelihood;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;

class HouseholdCodelistController extends Controller
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
}
