<?php


namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use DistributionBundle\DBAL\AssistanceTypeEnum;
use DistributionBundle\Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Utils\CodeLists;
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
        $data = CodeLists::mapArray(Assistance::TYPE_TO_STRING_MAPPING);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/assistances/types")
     *
     * @return JsonResponse
     */
    public function getTypes(): JsonResponse
    {
        $data = CodeLists::mapEnum(AssistanceTypeEnum::all());

        return $this->json(new Paginator($data));
    }
}