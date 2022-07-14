<?php

namespace NewApiBundle\Controller\OfflineApp;

use NewApiBundle\Entity\Beneficiary;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\BeneficiaryFilterInputType;
use NewApiBundle\Serializer\MapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BeneficiaryController extends AbstractOfflineAppController
{

    /**
     * @Rest\Get("/offline-app/v2/beneficiaries")
     *
     * @param Request                    $request
     * @param BeneficiaryFilterInputType $filter
     *
     * @return JsonResponse
     * @deprecated Application require only one beneficiary at a time
     */
    public function beneficiaries(Request $request, BeneficiaryFilterInputType $filter): JsonResponse
    {
        $beneficiaries = $this->getDoctrine()->getRepository(Beneficiary::class)->findByParams($filter);

        $response = $this->json($beneficiaries, Response::HTTP_OK, [], [MapperInterface::OFFLINE_APP => false]);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Rest\Get("/offline-app/v2/beneficiary/{id}")
     *
     * @param Beneficiary $beneficiary
     * @param Request     $request
     *
     * @return JsonResponse
     */
    public function beneficiary(Beneficiary $beneficiary, Request $request): JsonResponse
    {
        $response = $this->json($beneficiary);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }
}
