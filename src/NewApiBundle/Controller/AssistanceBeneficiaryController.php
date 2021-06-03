<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Enum\AssistanceTargetType;
use DistributionBundle\Utils\AssistanceBeneficiaryService;
use Exception;
use InvalidArgumentException;
use NewApiBundle\InputType\AddRemoveBeneficiaryToAssistanceInputType;
use NewApiBundle\InputType\AddRemoveCommunityToAssistanceInputType;
use NewApiBundle\InputType\AddRemoveInstitutionToAssistanceInputType;
use NewApiBundle\InputType\BeneficiaryFilterInputType;
use NewApiBundle\InputType\BeneficiaryOrderInputType;
use NewApiBundle\InputType\CommunityFilterType;
use NewApiBundle\InputType\CommunityOrderInputType;
use NewApiBundle\InputType\InstitutionFilterInputType;
use NewApiBundle\InputType\InstitutionOrderInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;

class AssistanceBeneficiaryController extends AbstractController
{
    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/assistances-beneficiaries")
     *
     * @param Assistance                 $assistance
     * @param BeneficiaryFilterInputType $filter
     * @param BeneficiaryOrderInputType  $orderBy
     * @param Pagination                 $pagination
     *
     * @return JsonResponse
     */
    public function assistanceBeneficiariesByAssistance(
        Assistance $assistance,
        BeneficiaryFilterInputType $filter,
        BeneficiaryOrderInputType $orderBy,
        Pagination $pagination
    ): JsonResponse
    {
        if ($assistance->getArchived()) {
            throw $this->createNotFoundException();
        }

        $assistanceBeneficiaries = $this->getDoctrine()->getRepository(AssistanceBeneficiary::class)->findBeneficiariesByAssistance($assistance, $filter, $orderBy, $pagination);

        return $this->json($assistanceBeneficiaries);
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/assistances-institutions")
     *
     * @param Assistance                 $assistance
     * @param InstitutionFilterInputType $filter
     * @param InstitutionOrderInputType  $orderBy
     * @param Pagination                 $pagination
     *
     * @return JsonResponse
     */
    public function assistanceInstitutionsByAssistance(
        Assistance $assistance,
        InstitutionFilterInputType $filter,
        InstitutionOrderInputType $orderBy,
        Pagination $pagination
    ): JsonResponse
    {
        if ($assistance->getArchived()) {
            throw $this->createNotFoundException();
        }

        $assistanceInstitutions = $this->getDoctrine()->getRepository(AssistanceBeneficiary::class)->findInstitutionsByAssistance($assistance, $filter, $orderBy, $pagination);

        return $this->json($assistanceInstitutions);
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/assistances-communities")
     *
     * @param Assistance              $assistance
     * @param CommunityFilterType     $filter
     * @param CommunityOrderInputType $orderBy
     * @param Pagination              $pagination
     *
     * @return JsonResponse
     */
    public function assistanceCommunitiesByAssistance(
        Assistance $assistance,
        CommunityFilterType $filter,
        CommunityOrderInputType $orderBy,
        Pagination $pagination
    ): JsonResponse
    {
        if ($assistance->getArchived()) {
            throw $this->createNotFoundException();
        }

        $assistanceCommunities = $this->getDoctrine()->getRepository(AssistanceBeneficiary::class)->findCommunitiesByAssistance($assistance, $filter, $orderBy, $pagination);

        return $this->json($assistanceCommunities);
    }

    /**
     * @Rest\Put("/web-app/v1/assistances/{id}/assistances-beneficiaries")
     *
     * @param Assistance                                $assistance
     * @param AddRemoveBeneficiaryToAssistanceInputType $inputType
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function addOrRemoveAssistanceBeneficiaries(Assistance $assistance, AddRemoveBeneficiaryToAssistanceInputType $inputType): JsonResponse
    {
        if ($assistance->getTargetType() !== AssistanceTargetType::HOUSEHOLD && $assistance->getTargetType() !== AssistanceTargetType::INDIVIDUAL) {
            throw new InvalidArgumentException('This assistance is only for households or individuals');
        }

        $data = ['beneficiaries' => [], 'justification' => $inputType->getJustification()];
        foreach ($inputType->getBeneficiaryIds() as $id) {
            $data['beneficiaries'][] = ['id' => $id];
        }

        /** @var AssistanceBeneficiaryService $assistanceBeneficiaryService */
        $assistanceBeneficiaryService = $this->get('distribution.assistance_beneficiary_service');

        if ($inputType->getAdded()) {
            $assistanceBeneficiaryService->addBeneficiaries($assistance, $data);
        } elseif ($inputType->getRemoved()) {
            $assistanceBeneficiaryService->removeBeneficiaries($assistance, $data);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Put("/web-app/v1/assistances/{id}/assistances-institutions")
     *
     * @param Assistance                                $assistance
     * @param AddRemoveInstitutionToAssistanceInputType $inputType
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function addOrRemoveAssistanceInstitutions(Assistance $assistance, AddRemoveInstitutionToAssistanceInputType $inputType): JsonResponse
    {
        if ($assistance->getTargetType() !== AssistanceTargetType::INSTITUTION) {
            throw new InvalidArgumentException('This assistance is only for institutions');
        }

        $data = ['beneficiaries' => [], 'justification' => $inputType->getJustification()];
        foreach ($inputType->getInstitutionIds() as $id) {
            $data['beneficiaries'][] = ['id' => $id];
        }

        /** @var AssistanceBeneficiaryService $assistanceBeneficiaryService */
        $assistanceBeneficiaryService = $this->get('distribution.assistance_beneficiary_service');

        if ($inputType->getAdded()) {
            $assistanceBeneficiaryService->addBeneficiaries($assistance, $data);
        } elseif ($inputType->getRemoved()) {
            $assistanceBeneficiaryService->removeBeneficiaries($assistance, $data);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Put("/web-app/v1/assistances/{id}/assistances-communities")
     *
     * @param Assistance                              $assistance
     * @param AddRemoveCommunityToAssistanceInputType $inputType
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function addOrRemoveAssistanceCommunities(Assistance $assistance, AddRemoveCommunityToAssistanceInputType $inputType): JsonResponse
    {
        if ($assistance->getTargetType() !== AssistanceTargetType::COMMUNITY) {
            throw new InvalidArgumentException('This assistance is only for communities');
        }

        $data = ['beneficiaries' => [], 'justification' => $inputType->getJustification()];
        foreach ($inputType->getCommunityIds() as $id) {
            $data['beneficiaries'][] = ['id' => $id];
        }

        /** @var AssistanceBeneficiaryService $assistanceBeneficiaryService */
        $assistanceBeneficiaryService = $this->get('distribution.assistance_beneficiary_service');

        if ($inputType->getAdded()) {
            $assistanceBeneficiaryService->addBeneficiaries($assistance, $data);
        } elseif ($inputType->getRemoved()) {
            $assistanceBeneficiaryService->removeBeneficiaries($assistance, $data);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
