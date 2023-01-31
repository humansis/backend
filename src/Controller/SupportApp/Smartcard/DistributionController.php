<?php

declare(strict_types=1);

namespace Controller\SupportApp\Smartcard;

use Controller\AbstractController;
use Enum\ModalityType;
use Exception;
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
        private readonly AssistanceBeneficiaryRepository $assistanceBeneficiaryRepository,
        private readonly SmartcardRepository $smartcardRepository
    ) {
    }

    /**
     * @Rest\Delete("/support-app/v1/smartcard/distribution")
     *
     * @param ResetingReliefPackageInputType $inputType
     * @return JsonResponse
     * @throws Exception
     */
    public function resetingReliefPackage(ResetingReliefPackageInputType $inputType): JsonResponse
    {
        $assistanceBeneficiary = $this->assistanceBeneficiaryRepository->findByAssistanceAndBeneficiary($inputType->getAssistanceId(), $inputType->getBeneficiaryId());
        if (!$assistanceBeneficiary) {
            throw new BadRequestHttpException("this beneficiary ({$inputType->getBeneficiaryId()}) doesn't belong to this assestant ({$inputType->getAssistanceId()})");
        }

        $smartcard = $this->smartcardRepository->findActiveBySerialNumber($inputType->getSmartcardCode());
        if ($smartcard) {
            if ($smartcard->getBeneficiary()->getId() !== $inputType->getBeneficiaryId()) {
                throw new BadRequestHttpException("This beneficiary doesn't have this smartcard ({$inputType->getSmartcardCode()})");
            }
        } else {
            throw new BadRequestHttpException("This smartcard doesn't exist or isn't activated");
        }

        $reliefPackages = $assistanceBeneficiary->getReliefPackages();
        if (count($reliefPackages) > 1) {
            throw new BadRequestHttpException("This beneficiary ({$inputType->getBeneficiaryId()}) has more than one ReliefPackage in the same assistance ({$inputType->getAssistanceId()})");
        }
        $smartcardDeposits = $assistanceBeneficiary->getSmartcardDeposits();
        if (count($smartcardDeposits) === 0) {
            throw new BadRequestHttpException("This beneficiary ({$inputType->getBeneficiaryId()}) did not receive a deposit for assistance ({$inputType->getAssistanceId()})");
        }

        if ($reliefPackages[0]->getModalityType() != ModalityType::SMART_CARD) {
            throw new BadRequestHttpException("Only Relief Packages that use the smartcard modality are allowed");
        }
        try {
            $this->assistanceDistributionService->deleteDistribution($reliefPackages, $smartcardDeposits);
        } catch (Throwable $ex) {
            throw new BadRequestHttpException($ex->getMessage());
        }
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
