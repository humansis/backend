<?php
declare(strict_types=1);

namespace NewApiBundle\Controller\SupportApp;

use BeneficiaryBundle\Repository\BeneficiaryRepository;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Enum\AssistanceTargetType;
use FOS\RestBundle\Controller\Annotations as Rest;
use InvalidArgumentException;
use NewApiBundle\Component\Assistance\Services\AssistanceBeneficiaryService;
use NewApiBundle\Controller\AbstractController;
use NewApiBundle\Exception\ManipulationOverValidatedAssistanceException;
use NewApiBundle\InputType\Assistance\AssistanceBeneficiariesOperationInputType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/support-app/v1/assistances/{id}/assistances-beneficiaries")
 */
class AssistanceBeneficiaryController extends AbstractController
{


    /**
     * @var BeneficiaryRepository
     */
    private $beneficiaryRepository;

    /**
     * @var AssistanceBeneficiaryService
     */
    private $assistanceBeneficiaryService;

    /**
     * @param BeneficiaryRepository           $beneficiaryRepository
     * @param AssistanceBeneficiaryService    $assistanceBeneficiaryService
     */
    public function __construct(BeneficiaryRepository $beneficiaryRepository,
                                AssistanceBeneficiaryService $assistanceBeneficiaryService)
    {
        $this->beneficiaryRepository = $beneficiaryRepository;
        $this->assistanceBeneficiaryService = $assistanceBeneficiaryService;
    }

    /**
     * @Rest\Put
     *
     * @param Assistance                                $assistance
     * @param AssistanceBeneficiariesOperationInputType $inputType
     *
     * @return JsonResponse
     * @throws \JsonException
     */
    public function addAssistanceBeneficiaries(
        Assistance                         $assistance,
        AssistanceBeneficiariesOperationInputType $inputType
    ): JsonResponse {
        if ($assistance->getTargetType() !== AssistanceTargetType::HOUSEHOLD
            && $assistance->getTargetType() !== AssistanceTargetType::INDIVIDUAL) {
            throw new InvalidArgumentException('This assistance is only for households or individuals');
        }
        try {
            $beneficiaries = $this->beneficiaryRepository->findByIdentities($inputType->getNumbers(), $inputType->getIdType());
            $this->assistanceBeneficiaryService->addBeneficiariesToAssistance($assistance, $beneficiaries, $inputType->getJustification());
        } catch (ManipulationOverValidatedAssistanceException $e) {
            return $this->json($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }


    /**
     * @Rest\Delete
     *
     * @param Assistance                         $assistance
     * @param AssistanceBeneficiariesOperationInputType $inputType
     *
     * @return JsonResponse
     */
    public function removeAssistanceBeneficiaries(
        Assistance                         $assistance,
        AssistanceBeneficiariesOperationInputType $inputType
    ): JsonResponse {
        if ($assistance->getTargetType() !== AssistanceTargetType::HOUSEHOLD
            && $assistance->getTargetType() !== AssistanceTargetType::INDIVIDUAL) {
            throw new InvalidArgumentException('This assistance is only for households or individuals');
        }
        try {
            $beneficiaries = $this->beneficiaryRepository->findByIdentities($inputType->getNumbers(), $inputType->getIdType());
            $this->assistanceBeneficiaryService->removeBeneficiariesFromAssistance($assistance, $beneficiaries, $inputType->getJustification());
        } catch (ManipulationOverValidatedAssistanceException $e) {
            return $this->json($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }


}
