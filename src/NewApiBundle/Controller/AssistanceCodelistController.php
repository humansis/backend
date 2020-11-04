<?php


namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use DistributionBundle\DBAL\AssistanceTypeEnum;
use DistributionBundle\Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class AssistanceCodelistController extends Controller
{
    /**
     * @Rest\Get("/assistances/targets")
     *
     * @return JsonResponse
     */
    public function getTargets(): JsonResponse
    {
        $data = self::map(Assistance::TYPE_TO_STRING_MAPPING);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/assistances/types")
     *
     * @return JsonResponse
     */
    public function getTypes(): JsonResponse
    {
        $data = self::map(AssistanceTypeEnum::all());

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