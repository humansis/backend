<?php

namespace Controller\OfflineApp;

use Doctrine\Persistence\ManagerRegistry;
use Entity\Beneficiary;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\BeneficiaryFilterInputType;
use Serializer\MapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BeneficiaryController extends AbstractOfflineAppController
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }
    /**
     *
     * @deprecated Application require only one beneficiary at a time
     */
    #[Rest\Get('/offline-app/v2/beneficiaries')]
    public function beneficiaries(Request $request, BeneficiaryFilterInputType $filter): JsonResponse
    {
        $beneficiaries = $this->managerRegistry->getRepository(Beneficiary::class)->findByParams($filter);

        $response = $this->json($beneficiaries, Response::HTTP_OK, [], [MapperInterface::OFFLINE_APP => false]);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    #[Rest\Get('/offline-app/v2/beneficiary/{id}')]
    public function beneficiary(Beneficiary $beneficiary, Request $request): JsonResponse
    {
        $response = $this->json($beneficiary);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }
}
