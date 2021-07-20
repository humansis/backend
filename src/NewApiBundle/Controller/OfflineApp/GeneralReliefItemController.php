<?php

declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp;

use DistributionBundle\Entity\GeneralReliefItem;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Controller\AbstractController;
use NewApiBundle\InputType\GeneralReliefPatchInputType;
use Symfony\Component\HttpFoundation\JsonResponse;

class GeneralReliefItemController extends AbstractController
{
    /**
     * @Rest\Patch("/offline-app/v2/general-relief-items/{id}")
     *
     * @param GeneralReliefItem           $object
     * @param GeneralReliefPatchInputType $inputType
     *
     * @return JsonResponse
     */
    public function patch(GeneralReliefItem $object, GeneralReliefPatchInputType $inputType): JsonResponse
    {
        $this->get('distribution.assistance_service')->patchGeneralReliefItem($object, $inputType);

        return $this->json($object);
    }
}
