<?php

namespace BeneficiaryBundle\Controller;

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
        $data = self::map(Livelihood::values());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/households/assets")
     *
     * @return JsonResponse
     */
    public function getAssets(): JsonResponse
    {
        $data = self::map(Household::ASSETS);

        return $this->json(new Paginator($data));
    }

    private static function map(iterable $list): array
    {
        $data = [];
        foreach ($list as $key => $value) {
            $data[] = ['code' => $key, 'value' => $value];
        }

        return $data;
    }
}
