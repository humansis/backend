<?php

declare(strict_types=1);

namespace Controller;

use Entity\Beneficiary;
use Exception\AssistanceTargetMismatchException;
use Repository\BeneficiaryRepository;
use Repository\CommunityRepository;
use Repository\InstitutionRepository;
use Entity;
use Entity\Assistance;
use Enum\AssistanceTargetType;
use Repository\AssistanceBeneficiaryRepository;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use InvalidArgumentException;
use Component\Assistance\AssistanceFactory;
use Component\Assistance\Domain;
use Component\Assistance\Services\AssistanceBeneficiaryService;
use Exception\ManipulationOverValidatedAssistanceException;
use InputType\AddRemoveAbstractBeneficiaryToAssistanceInputType;
use InputType\AddRemoveCommunityToAssistanceInputType;
use InputType\AddRemoveInstitutionToAssistanceInputType;
use InputType\Assistance\AssistanceBeneficiariesOperationInputType;
use InputType\BeneficiaryFilterInputType;
use InputType\BeneficiaryOrderInputType;
use InputType\CommunityFilterType;
use InputType\CommunityOrderInputType;
use InputType\InstitutionFilterInputType;
use InputType\InstitutionOrderInputType;
use OutputType\Assistance\AssistanceBeneficiaryOperationOutputType;
use Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AssistanceBeneficiaryController extends AbstractController
{
    public const MAX_ALLOWED_OPERATIONS = 5000;

    /**
     * @var BeneficiaryRepository
     */
    private $beneficiaryRepository;

    /**
     * @var AssistanceBeneficiaryService
     */
    private $assistanceBeneficiaryService;

    /**
     * @var AssistanceBeneficiaryRepository
     */
    private $assistanceBeneficiaryRepository;

    /**
     * @param AssistanceBeneficiaryRepository $assistanceBeneficiaryRepository
     * @param BeneficiaryRepository $beneficiaryRepository
     * @param AssistanceBeneficiaryService $assistanceBeneficiaryService
     */
    public function __construct(
        AssistanceBeneficiaryRepository $assistanceBeneficiaryRepository,
        BeneficiaryRepository $beneficiaryRepository,
        AssistanceBeneficiaryService $assistanceBeneficiaryService
    ) {
        $this->assistanceBeneficiaryRepository = $assistanceBeneficiaryRepository;
        $this->beneficiaryRepository = $beneficiaryRepository;
        $this->assistanceBeneficiaryService = $assistanceBeneficiaryService;
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/assistances-beneficiaries")
     *
     * @param Entity\Assistance $assistance
     * @param BeneficiaryFilterInputType $filter
     * @param BeneficiaryOrderInputType $orderBy
     * @param Pagination $pagination
     *
     * @return JsonResponse
     */
    public function assistanceBeneficiariesByAssistance(
        Entity\Assistance $assistance,
        BeneficiaryFilterInputType $filter,
        BeneficiaryOrderInputType $orderBy,
        Pagination $pagination
    ): JsonResponse {
        if ($assistance->getArchived()) {
            throw $this->createNotFoundException();
        }

        $assistanceBeneficiaries = $this->assistanceBeneficiaryRepository->findBeneficiariesByAssistance(
            $assistance,
            $filter,
            $orderBy,
            $pagination
        );

        return $this->json($assistanceBeneficiaries);
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/assistances-institutions")
     *
     * @param Entity\Assistance $assistance
     * @param InstitutionFilterInputType $filter
     * @param InstitutionOrderInputType $orderBy
     * @param Pagination $pagination
     *
     * @return JsonResponse
     */
    public function assistanceInstitutionsByAssistance(
        Entity\Assistance $assistance,
        InstitutionFilterInputType $filter,
        InstitutionOrderInputType $orderBy,
        Pagination $pagination
    ): JsonResponse {
        if ($assistance->getArchived()) {
            throw $this->createNotFoundException();
        }

        $assistanceInstitutions = $this->assistanceBeneficiaryRepository->findInstitutionsByAssistance(
            $assistance,
            $filter,
            $orderBy,
            $pagination
        );

        return $this->json($assistanceInstitutions);
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/assistances-communities")
     *
     * @param Entity\Assistance $assistance
     * @param CommunityFilterType $filter
     * @param CommunityOrderInputType $orderBy
     * @param Pagination $pagination
     *
     * @return JsonResponse
     */
    public function assistanceCommunitiesByAssistance(
        Entity\Assistance $assistance,
        CommunityFilterType $filter,
        CommunityOrderInputType $orderBy,
        Pagination $pagination
    ): JsonResponse {
        if ($assistance->getArchived()) {
            throw $this->createNotFoundException();
        }

        $assistanceCommunities = $this->assistanceBeneficiaryRepository->findCommunitiesByAssistance(
            $assistance,
            $filter,
            $orderBy,
            $pagination
        );

        return $this->json($assistanceCommunities);
    }

    /**
     * @Rest\Delete("/web-app/v1/assistances/{id}/assistances-beneficiaries")
     * @param Assistance $assistanceRoot
     * @param AssistanceBeneficiariesOperationInputType $inputType
     *
     * @return JsonResponse
     */
    public function removeAssistanceBeneficiaries(
        Entity\Assistance $assistanceRoot,
        AssistanceBeneficiariesOperationInputType $inputType
    ): JsonResponse {
        $this->checkAssistance($assistanceRoot);
        $this->checkAllowedOperations($inputType);

        try {
            $beneficiaries = $this->getBeneficiariesForAssistanceBeneficiaryChange($inputType);
            $output = $this->prepareBeneficiariesForChange($assistanceRoot, $beneficiaries, $inputType);

            $output = $this->assistanceBeneficiaryService->removeBeneficiariesFromAssistance(
                $output,
                $assistanceRoot,
                $beneficiaries,
                $inputType->getJustification()
            );

            return $this->json($output, Response::HTTP_OK);
        } catch (ManipulationOverValidatedAssistanceException $exception) {
            return $this->json($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Rest\Put("/web-app/v1/assistances/{id}/assistances-beneficiaries")
     * @param Assistance $assistanceRoot
     * @param AssistanceBeneficiariesOperationInputType $inputType
     *
     * @return JsonResponse
     */
    public function addAssistanceBeneficiaries(
        Entity\Assistance $assistanceRoot,
        AssistanceBeneficiariesOperationInputType $inputType
    ): JsonResponse {
        $this->checkAssistance($assistanceRoot);
        $this->checkAllowedOperations($inputType);

        try {
            $beneficiaries = $this->getBeneficiariesForAssistanceBeneficiaryChange($inputType);
            $output = $this->prepareBeneficiariesForChange($assistanceRoot, $beneficiaries, $inputType);

            $output = $this->assistanceBeneficiaryService->addBeneficiariesToAssistance(
                $output,
                $assistanceRoot,
                $beneficiaries,
                $inputType->getJustification()
            );

            return $this->json($output, Response::HTTP_OK);
        } catch (ManipulationOverValidatedAssistanceException | AssistanceTargetMismatchException $exception) {
            return $this->json($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    private function actualizeBeneficiary(
        Domain\Assistance $assistance,
        array $idList,
        EntityRepository $repository,
        AddRemoveAbstractBeneficiaryToAssistanceInputType $inputType
    ): void {
        foreach ($idList as $id) {
            $beneficiary = $repository->find($id);
            if ($inputType->getAdded()) {
                $assistance->addBeneficiary($beneficiary, $inputType->getJustification());
            } elseif ($inputType->getRemoved()) {
                $assistance->removeBeneficiary($beneficiary, $inputType->getJustification());
            }
        }
    }

    /**
     * @Rest\Put("/web-app/v1/assistances/{id}/assistances-institutions")
     *
     * @param Entity\Assistance $assistanceRoot
     * @param AddRemoveInstitutionToAssistanceInputType $inputType
     * @param AssistanceFactory $factory
     * @param InstitutionRepository $repository
     *
     * @return JsonResponse
     */
    public function addOrRemoveAssistanceInstitutions(
        Entity\Assistance $assistanceRoot,
        AddRemoveInstitutionToAssistanceInputType $inputType,
        AssistanceFactory $factory,
        InstitutionRepository $repository
    ): JsonResponse {
        if ($assistanceRoot->getTargetType() !== AssistanceTargetType::INSTITUTION) {
            throw new InvalidArgumentException('This assistance is only for institutions');
        }

        $this->actualizeBeneficiary(
            $factory->hydrate($assistanceRoot),
            $inputType->getInstitutionIds(),
            $repository,
            $inputType
        );

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Put("/web-app/v1/assistances/{id}/assistances-communities")
     *
     * @param Entity\Assistance $assistanceRoot
     * @param AddRemoveCommunityToAssistanceInputType $inputType
     * @param AssistanceFactory $factory
     * @param CommunityRepository $repository
     *
     * @return JsonResponse
     */
    public function addOrRemoveAssistanceCommunities(
        Entity\Assistance $assistanceRoot,
        AddRemoveCommunityToAssistanceInputType $inputType,
        AssistanceFactory $factory,
        CommunityRepository $repository
    ): JsonResponse {
        if ($assistanceRoot->getTargetType() !== AssistanceTargetType::COMMUNITY) {
            throw new InvalidArgumentException('This assistance is only for communities');
        }

        $this->actualizeBeneficiary(
            $factory->hydrate($assistanceRoot),
            $inputType->getCommunityIds(),
            $repository,
            $inputType
        );

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param AssistanceBeneficiariesOperationInputType $inputType
     *
     * @return Beneficiary[]
     */
    private function getBeneficiariesForAssistanceBeneficiaryChange(AssistanceBeneficiariesOperationInputType $inputType): array
    {
        if ($inputType->hasDocumentNumbers()) {
            return $this->beneficiaryRepository->findByIdentities(
                $inputType->getDocumentNumbers(),
                $inputType->getDocumentType()
            );
        }

        return $this->beneficiaryRepository->findByIds($inputType->getBeneficiaryIds());
    }

    /**
     * @param Assistance $assistance
     * @param Beneficiary[] $beneficiaries
     * @param AssistanceBeneficiariesOperationInputType $inputType
     *
     * @return AssistanceBeneficiaryOperationOutputType
     */
    private function prepareBeneficiariesForChange(
        Assistance $assistance,
        array $beneficiaries,
        AssistanceBeneficiariesOperationInputType $inputType
    ): AssistanceBeneficiaryOperationOutputType {
        if ($inputType->hasDocumentNumbers()) {
            return $this->assistanceBeneficiaryService->prepareOutputForDocumentNumbers(
                $beneficiaries,
                $inputType->getDocumentNumbers(),
                $inputType->getDocumentType()
            );
        }

        return $this->assistanceBeneficiaryService->prepareOutputForBeneficiaryIds(
            $beneficiaries,
            $inputType->getBeneficiaryIds()
        );
    }

    /**
     * @param Assistance $assistance
     *
     * @return void
     */
    private function checkAssistance(Assistance $assistance): void
    {
        if (
            $assistance->getTargetType() !== AssistanceTargetType::HOUSEHOLD
            && $assistance->getTargetType() !== AssistanceTargetType::INDIVIDUAL
        ) {
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
        $operations = 0;
        if ($inputType->getBeneficiaryIds() !== null) {
            $operations = count($inputType->getBeneficiaryIds());
        } else {
            if ($inputType->getDocumentNumbers() !== null) {
                $operations = count($inputType->getDocumentNumbers());
            }
        }

        if ($operations >= self::MAX_ALLOWED_OPERATIONS) {
            throw new BadRequestHttpException(
                "This endpoint allows only to execute " . self::MAX_ALLOWED_OPERATIONS . " operations. You try to execute {$operations} operations."
            );
        }
    }
}
