<?php
declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp;

use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use NewApiBundle\InputType\BeneficiaryFilterInputType;
use NewApiBundle\InputType\BeneficiaryOrderInputType;
use NewApiBundle\InputType\CommunityFilterType;
use NewApiBundle\InputType\CommunityOrderInputType;
use NewApiBundle\InputType\InstitutionFilterInputType;
use NewApiBundle\InputType\InstitutionOrderInputType;
use NewApiBundle\Request\Pagination;
use NewApiBundle\Serializer\MapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AssistanceBeneficiaryController extends AbstractOfflineAppController
{
    /**
     * @Rest\Get("/offline-app/v2/assistances/{id}/assistances-beneficiaries")
     *
     * @param Request                    $request
     * @param Assistance                 $assistance
     * @param BeneficiaryFilterInputType $filter
     * @param BeneficiaryOrderInputType  $orderBy
     * @param Pagination                 $pagination
     *
     * @return JsonResponse
     */
    public function assistanceBeneficiariesByAssistance(
        Request $request,
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

        $response = $this->json($assistanceBeneficiaries);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Rest\Get("/offline-app/v3/assistances/{id}/targets/beneficiaries")
     *
     * @param Request                    $request
     * @param Assistance                 $assistance
     * @param BeneficiaryFilterInputType $filter
     * @param BeneficiaryOrderInputType  $orderBy
     * @param Pagination                 $pagination
     *
     * @return JsonResponse
     */
    public function beneficiaryTargetByAssistance(
        Request $request,
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

        $response = $this->json($assistanceBeneficiaries, Response::HTTP_OK, [], [MapperInterface::OFFLINE_APP => true, 'expanded' => true]);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Rest\Get("/offline-app/v1/assistances/{id}/assistances-institutions")
     *
     * @param Request                    $request
     * @param Assistance                 $assistance
     * @param InstitutionFilterInputType $filter
     * @param InstitutionOrderInputType  $orderBy
     * @param Pagination                 $pagination
     *
     * @return JsonResponse
     */
    public function assistanceInstitutionsByAssistance(
        Request $request,
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

        $response = $this->json($assistanceInstitutions, Response::HTTP_OK, [], [MapperInterface::OFFLINE_APP => false]);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Rest\Get("/offline-app/v1/assistances/{id}/assistances-communities")
     *
     * @param Request                 $request
     * @param Assistance              $assistance
     * @param CommunityFilterType     $filter
     * @param CommunityOrderInputType $orderBy
     * @param Pagination              $pagination
     *
     * @return JsonResponse
     */
    public function assistanceCommunitiesByAssistance(
        Request $request,
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

        $response = $this->json($assistanceCommunities, Response::HTTP_OK, [], [MapperInterface::OFFLINE_APP => false]);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

}
