<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use DistributionBundle\DBAL\AssistanceTypeEnum;
use DistributionBundle\Enum\AssistanceTargetType;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Utils\CodeLists;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Cache(expires="+5 days", public=true)
 */
class AssistanceCodelistController extends AbstractController
{
    /**
     * @Rest\Get("/assistances/targets")
     *
     * @return JsonResponse
     */
    public function getTargets(): JsonResponse
    {
        $data = CodeLists::mapEnum(AssistanceTargetType::values());

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
