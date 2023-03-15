<?php

declare(strict_types=1);

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
    public function __construct(private readonly AssistanceDistributionService $assistanceDistributionService)
    {
    }

    #[Rest\Patch('/support-app/v1/relief-packages/{id}')]
    public function update(ReliefPackage $reliefpackage, UpdateReliefPackageInputType $inputpackages): JsonResponse
    {
        $reliefpackage = $this->assistanceDistributionService->update($reliefpackage, $inputpackages);

        return $this->json($reliefpackage);
    }
}
