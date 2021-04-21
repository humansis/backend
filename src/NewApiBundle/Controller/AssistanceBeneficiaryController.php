<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use NewApiBundle\InputType\BeneficiaryFilterInputType;
use NewApiBundle\InputType\BeneficiaryOrderInputType;
use NewApiBundle\InputType\CommunityFilterType;
use NewApiBundle\InputType\CommunityOrderInputType;
use NewApiBundle\InputType\InstitutionFilterInputType;
use NewApiBundle\InputType\InstitutionOrderInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;

class AssistanceBeneficiaryController extends AbstractController
{
    /**
     * @Rest\Get("/assistances/{id}/assistances-beneficiaries")
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
     * @Rest\Get("/assistances/{id}/assistances-institutions")
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
     * @Rest\Get("/assistances/{id}/assistances-communities")
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
}
