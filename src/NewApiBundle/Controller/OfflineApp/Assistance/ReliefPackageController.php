<?php
declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp\Assistance;

use DistributionBundle\Entity\Assistance;
use DistributionBundle\Utils\AssistanceDistributionService;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Assistance\AssistanceFactory;
use NewApiBundle\Controller\OfflineApp\AbstractOfflineAppController;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\InputType\Assistance\DistributeBeneficiaryReliefPackagesInputType;
use NewApiBundle\InputType\Assistance\DistributeReliefPackagesInputType;
use NewApiBundle\Repository\Assistance\ReliefPackageRepository;
use NewApiBundle\Workflow\ReliefPackageTransitions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Workflow\Registry;

class ReliefPackageController extends AbstractOfflineAppController
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
     * @Rest\Patch("/offline-app/v1/assistances/relief-packages/distribute")
     * @ParamConverter(class="NewApiBundle\InputType\Assistance\DistributeReliefPackagesInputType[]", name="packages", converter="input_type_converter")
     *
     * @param DistributeReliefPackagesInputType[] $packages
     *
     * @return JsonResponse
     */
    public function distributePackages(
        array                   $packages
    ): JsonResponse {

        $this->assistanceDistributionService->distributeByReliefIds($packages, $this->getUser());
        return $this->json(true);
    }
}
