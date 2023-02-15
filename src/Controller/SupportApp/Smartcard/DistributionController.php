<?php

declare(strict_types=1);

namespace Controller\SupportApp\Smartcard;

use Component\Smartcard\Deposit\DepositFactory;
use Component\Smartcard\Deposit\Exception\DoubledDepositException;
use Controller\AbstractController;
use Doctrine\DBAL\Exception;
use Exception\RemoveDistributionException;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\ResetReliefPackageInputType;
use InputType\Smartcard\ManualDistributionInputType;
use Psr\Cache\InvalidArgumentException;
use Services\AssistanceDistributionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DistributionController extends AbstractController
{
    public function __construct(
        private readonly AssistanceDistributionService $assistanceDistributionService,
        private readonly DepositFactory $depositFactory,
    ) {
    }

    /**
     * @throws Exception
     */
    #[Rest\Delete('/support-app/v1/smartcard/distribution')]
    public function removeDistribution(ResetReliefPackageInputType $inputType): JsonResponse
    {
        try {
            $this->assistanceDistributionService->deleteDistribution($inputType);
        } catch (RemoveDistributionException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Rest\Post('/support-app/v1/smartcard/distribution')]
    public function createDistribution(ManualDistributionInputType $manualDistributionInputType): JsonResponse
    {
        try {
            $this->depositFactory->createForSupportApp($manualDistributionInputType);
        } catch (DoubledDepositException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        return $this->json(null, Response::HTTP_CREATED);
    }
}
