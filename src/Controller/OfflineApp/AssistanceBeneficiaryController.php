<?php

declare(strict_types=1);

namespace Controller\OfflineApp;

use Entity\Assistance;
use Repository\AssistanceBeneficiaryRepository;
use InputType\BeneficiaryFilterInputType;
use InputType\BeneficiaryOrderInputType;
use InputType\CommunityFilterType;
use InputType\CommunityOrderInputType;
use InputType\InstitutionFilterInputType;
use InputType\InstitutionOrderInputType;
use Request\Pagination;
use Serializer\MapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AssistanceBeneficiaryController extends AbstractOfflineAppController
{
    public function __construct(private readonly AssistanceBeneficiaryRepository $assistanceBeneficiaryRepository)
    {
    }

    /**
     * @Rest\Get("/offline-app/v2/assistances/{id}/assistances-beneficiaries")
     *
     *
     */
    public function assistanceBeneficiariesByAssistance(
        Request $request,
        Assistance $assistance,
        BeneficiaryFilterInputType $filter,
        BeneficiaryOrderInputType $orderBy,
        Pagination $pagination
    ): JsonResponse {
        if ($assistance->getArchived()) {
            throw $this->createNotFoundException();
        }

        $assistanceBeneficiaries = $this->assistanceBeneficiaryRepository
            ->findBeneficiariesByAssistance($assistance, $filter, $orderBy, $pagination);

        $response = $this->json($assistanceBeneficiaries);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Rest\Get("/offline-app/{version}/assistances/{id}/targets/beneficiaries")
     *
     *
     */
    public function beneficiaryTargetByAssistance(
        string $version,
        Request $request,
        Assistance $assistance,
        BeneficiaryFilterInputType $filter,
        BeneficiaryOrderInputType $orderBy,
        Pagination $pagination
    ): JsonResponse {
        if (!in_array($version, ['v3', 'v4'])) {
            throw $this->createNotFoundException("Endpoint in version $version is not supported");
        }

        if ($assistance->getArchived()) {
            throw $this->createNotFoundException();
        }

        $assistanceBeneficiaries = $this->assistanceBeneficiaryRepository
            ->findBeneficiariesByAssistance(
                $assistance,
                $filter,
                $orderBy,
                $pagination,
                [AssistanceBeneficiaryRepository::SEARCH_CONTEXT_NOT_REMOVED => true]
            );

        $response = $this->json(
            $assistanceBeneficiaries,
            Response::HTTP_OK,
            [],
            [MapperInterface::OFFLINE_APP => true, 'expanded' => true, 'version' => $version]
        );
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Rest\Get("/offline-app/v1/assistances/{id}/assistances-institutions")
     *
     *
     */
    public function assistanceInstitutionsByAssistance(
        Request $request,
        Assistance $assistance,
        InstitutionFilterInputType $filter,
        InstitutionOrderInputType $orderBy,
        Pagination $pagination
    ): JsonResponse {
        if ($assistance->getArchived()) {
            throw $this->createNotFoundException();
        }

        $assistanceInstitutions = $this->assistanceBeneficiaryRepository
            ->findInstitutionsByAssistance(
                $assistance,
                $filter,
                $orderBy,
                $pagination,
                [AssistanceBeneficiaryRepository::SEARCH_CONTEXT_NOT_REMOVED => true]
            );

        $response = $this->json($assistanceInstitutions, Response::HTTP_OK, [], [MapperInterface::OFFLINE_APP => false]);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Rest\Get("/offline-app/v1/assistances/{id}/assistances-communities")
     *
     *
     */
    public function assistanceCommunitiesByAssistance(
        Request $request,
        Assistance $assistance,
        CommunityFilterType $filter,
        CommunityOrderInputType $orderBy,
        Pagination $pagination
    ): JsonResponse {
        if ($assistance->getArchived()) {
            throw $this->createNotFoundException();
        }

        $assistanceCommunities = $this->assistanceBeneficiaryRepository
            ->findCommunitiesByAssistance(
                $assistance,
                $filter,
                $orderBy,
                $pagination,
                [AssistanceBeneficiaryRepository::SEARCH_CONTEXT_NOT_REMOVED => true]
            );

        $response = $this->json($assistanceCommunities, Response::HTTP_OK, [], [MapperInterface::OFFLINE_APP => false]);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }
}
