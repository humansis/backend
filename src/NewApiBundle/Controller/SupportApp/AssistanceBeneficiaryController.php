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
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Rest\Route("/support-app/v1/assistances/{id}/assistances-beneficiaries")
 */
class AssistanceBeneficiaryController extends AbstractController
{

    const MAX_ALLOWED_OPERATIONS = 5000;

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
        $this->checkRole('ROLE_ADMIN');
        $this->checkAssistance($assistance);
        $this->checkAllowedOperations($inputType);
        try {
            $beneficiaries = $this->beneficiaryRepository->findByIdentities($inputType->getNumbers(), $inputType->getIdType());
            $output = $this->assistanceBeneficiaryService->prepareOutput($beneficiaries,$inputType->getNumbers(), $inputType->getIdType());
            $output = $this->assistanceBeneficiaryService->addBeneficiariesToAssistance($output, $assistance, $beneficiaries, $inputType->getJustification());
        } catch (ManipulationOverValidatedAssistanceException $e) {
            return $this->json($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        return $this->json($output, Response::HTTP_OK);
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
        $this->checkRole('ROLE_ADMIN');
        $this->checkAssistance($assistance);
        $this->checkAllowedOperations($inputType);
        try {
            $beneficiaries = $this->beneficiaryRepository->findByIdentities($inputType->getNumbers(), $inputType->getIdType());
            $output = $this->assistanceBeneficiaryService->prepareOutput($beneficiaries,$inputType->getNumbers(), $inputType->getIdType());
            $output = $this->assistanceBeneficiaryService->removeBeneficiariesFromAssistance($output, $assistance, $beneficiaries, $inputType->getJustification());
        } catch (ManipulationOverValidatedAssistanceException $e) {
            return $this->json($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        return $this->json($output, Response::HTTP_OK);
    }

    /**
     * @param Assistance $assistance
     *
     * @return void
     */
    private function checkRole($role): void
    {
        if(!in_array($role, $this->getUser()->getRoles())) {
            throw new AccessDeniedHttpException("This is allowed only for role '{$role}'.");
        }
    }

    /**
     * @param Assistance $assistance
     *
     * @return void
     */
    private function checkAssistance(Assistance $assistance): void
    {
        if ($assistance->getTargetType() !== AssistanceTargetType::HOUSEHOLD
            && $assistance->getTargetType() !== AssistanceTargetType::INDIVIDUAL) {
            throw new BadRequestHttpException('This assistance is only for households or individuals');
        }
    }

    /**
     * @param AssistanceBeneficiariesOperationInputType $inputType
     *
     * @return void
     */
    private function checkAllowedOperations(AssistanceBeneficiariesOperationInputType $inputType): void
    {
        $operations = count($inputType->getNumbers());
        if ($operations >= self::MAX_ALLOWED_OPERATIONS) {
            throw new BadRequestHttpException("This endpoint allows only to execute ".self::MAX_ALLOWED_OPERATIONS." operations. You try to execute {$operations} operations.");
        }
    }

}
