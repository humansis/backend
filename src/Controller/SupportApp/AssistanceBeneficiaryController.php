<?php

declare(strict_types=1);

namespace Controller\SupportApp;

use Controller\AbstractController;
use Entity\Assistance;
use Enum\AssistanceTargetType;
use Exception\ManipulationOverValidatedAssistanceException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\Assistance\Services\AssistanceBeneficiaryService;
use InputType\Assistance\AssistanceBeneficiariesOperationInputType;
use JsonException;
use Repository\BeneficiaryRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Rest\Route("/support-app/v1/assistances/{id}/assistances-beneficiaries")
 */
class AssistanceBeneficiaryController extends AbstractController
{
    final public const MAX_ALLOWED_OPERATIONS = 5000;

    public function __construct(private readonly BeneficiaryRepository $beneficiaryRepository, private readonly AssistanceBeneficiaryService $assistanceBeneficiaryService)
    {
    }

    /**
     * @Rest\Put
     * @param Request $request
     * @param Assistance $assistance
     * @param AssistanceBeneficiariesOperationInputType $inputType
     * @return JsonResponse
     */
    public function addAssistanceBeneficiaries(
        Request $request,
        Assistance $assistance,
        AssistanceBeneficiariesOperationInputType $inputType
    ): JsonResponse {
        $this->checkRole('ROLE_ADMIN');
        $this->checkAssistance($assistance);
        $this->checkAllowedOperations($inputType);
        $countryCode =  $this->getCountryCode($request);
        try {
            $beneficiaries = $this->beneficiaryRepository->findByIdentities(
                $inputType->getDocumentNumbers(),
                $inputType->getDocumentType(),
                $countryCode

            );
            $output = $this->assistanceBeneficiaryService->prepareOutputForDocumentNumbers(
                $beneficiaries,
                $inputType->getDocumentNumbers(),
                $inputType->getDocumentType()
            );
            $output = $this->assistanceBeneficiaryService->addBeneficiariesToAssistance(
                $output,
                $assistance,
                $beneficiaries,
                $inputType->getJustification()
            );
        } catch (ManipulationOverValidatedAssistanceException $e) {
            return $this->json($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->json($output, Response::HTTP_OK);
    }

    /**
     * @Rest\Delete
     *
     *
     */
    public function removeAssistanceBeneficiaries(
        Request $request,
        Assistance $assistance,
        AssistanceBeneficiariesOperationInputType $inputType
    ): JsonResponse {
        $this->checkRole('ROLE_ADMIN');
        $this->checkAssistance($assistance);
        $this->checkAllowedOperations($inputType);
        $countryCode =  $this->getCountryCode($request);
        try {
            $beneficiaries = $this->beneficiaryRepository->findByIdentities(
                $inputType->getDocumentNumbers(),
                $inputType->getDocumentType(),
                $countryCode
            );
            $output = $this->assistanceBeneficiaryService->prepareOutputForDocumentNumbers(
                $beneficiaries,
                $inputType->getDocumentNumbers(),
                $inputType->getDocumentType()
            );
            $output = $this->assistanceBeneficiaryService->removeBeneficiariesFromAssistance(
                $output,
                $assistance,
                $beneficiaries,
                $inputType->getJustification()
            );
        } catch (ManipulationOverValidatedAssistanceException $e) {
            return $this->json($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->json($output, Response::HTTP_OK);
    }

    private function checkRole(string $role): void
    {
        if (!in_array($role, $this->getUser()->getRoles())) {
            throw new AccessDeniedHttpException("This is allowed only for role '{$role}'.");
        }
    }

    private function checkAssistance(Assistance $assistance): void
    {
        if (
            $assistance->getTargetType() !== AssistanceTargetType::HOUSEHOLD
            && $assistance->getTargetType() !== AssistanceTargetType::INDIVIDUAL
        ) {
            throw new BadRequestHttpException('This assistance is only for households or individuals');
        }
    }

    private function checkAllowedOperations(AssistanceBeneficiariesOperationInputType $inputType): void
    {
        $operations = count($inputType->getDocumentNumbers());
        if ($operations >= self::MAX_ALLOWED_OPERATIONS) {
            throw new BadRequestHttpException(
                "This endpoint allows only to execute " . self::MAX_ALLOWED_OPERATIONS . " operations. You try to execute {$operations} operations."
            );
        }
    }
}
