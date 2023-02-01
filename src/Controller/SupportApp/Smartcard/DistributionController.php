<?php

declare(strict_types=1);

namespace Controller\SupportApp\Smartcard;

use Controller\AbstractController;
use Entity\Smartcard;
use Enum\ModalityType;
use Exception;
use Exception\RemoveDistribtuionException;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\ResetingReliefPackageInputType;
use Repository\AssistanceBeneficiaryRepository;
use Repository\SmartcardRepository;
use Services\AssistanceDistributionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class DistributionController extends AbstractController
{
    public function __construct(
        private readonly AssistanceDistributionService $assistanceDistributionService,
    ) {
    }

    /**
     * @Rest\Delete("/support-app/v1/smartcard/distribution")
     *
     * @param ResetingReliefPackageInputType $inputType
     * @return JsonResponse
     */
    public function removeDistribution(ResetingReliefPackageInputType $inputType): JsonResponse
    {
        try {
            $this->assistanceDistributionService->deleteDistribution($inputType);
        } catch (RemoveDistribtuionException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
