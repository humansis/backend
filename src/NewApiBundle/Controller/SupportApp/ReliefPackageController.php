<?php

namespace NewApiBundle\Controller\SupportApp;

use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Controller\AbstractController;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Services\AssistanceDistributionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use NewApiBundle\InputType\Assistance\UpdateReliefPackagesInputType;


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
     * @param ReliefPackage                 $reliefpackage
     * @param UpdateReliefPackagesInputType $inputpackages
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function update(ReliefPackage $reliefpackage,UpdateReliefPackagesInputType $inputpackages) :JsonResponse
    {
        $reliefpackage = $this->assistanceDistributionService->update($reliefpackage,$inputpackages);
        return $this->json($reliefpackage);
    }

}
