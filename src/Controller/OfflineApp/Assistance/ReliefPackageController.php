<?php

declare(strict_types=1);

namespace Controller\OfflineApp\Assistance;

use FOS\RestBundle\Controller\Annotations as Rest;
use Controller\OfflineApp\AbstractOfflineAppController;
use InputType\Assistance\DistributeReliefPackagesInputType;
use Services\AssistanceDistributionService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ReliefPackageController extends AbstractOfflineAppController
{
    public function __construct(private readonly AssistanceDistributionService $assistanceDistributionService)
    {
    }

    /**
     *
     * @param DistributeReliefPackagesInputType[] $packages
     */
    #[Rest\Patch('/offline-app/v1/assistances/relief-packages/distribute')]
    #[ParamConverter('packages', class: 'InputType\Assistance\DistributeReliefPackagesInputType[]', converter: 'input_type_converter')]
    public function distributePackages(
        array $packages
    ): Response {
        $distributionOutput = $this->assistanceDistributionService->distributeByReliefIds($packages, $this->getUser());
        if (count($distributionOutput->getAlreadyDistributed()) > 0) {
            return new Response('', Response::HTTP_ACCEPTED);
        }

        return new Response();
    }
}
