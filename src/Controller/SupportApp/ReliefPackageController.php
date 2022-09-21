<?php

namespace Controller\SupportApp;

use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use Controller\AbstractController;
use Entity\Assistance\ReliefPackage;
use Services\AssistanceDistributionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use InputType\Assistance\UpdateReliefPackageInputType;


class ReliefPackageController extends AbstractController
{
    /**
     * @var AssistanceDistributionService
     */
    private $assistanceDistributionService;

    /**
     * @param AssistanceDistributionService $assistanceDistributionService
     */
    public function __construct(AssistanceDistributionService $assistanceDistributionService)
    {
        $this->assistanceDistributionService = $assistanceDistributionService;
    }

    /**
     * @Rest\Patch("/support-app/v1/relief-packages/{id}")
     *
     * @param ReliefPackage                $reliefpackage
     * @param UpdateReliefPackageInputType $inputpackages
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function update(ReliefPackage $reliefpackage, UpdateReliefPackageInputType $inputpackages) :JsonResponse
    {
        $reliefpackage = $this->assistanceDistributionService->update($reliefpackage,$inputpackages);
        return $this->json($reliefpackage);
    }

}
