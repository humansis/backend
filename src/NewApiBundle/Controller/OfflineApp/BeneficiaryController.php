<?php

namespace NewApiBundle\Controller\OfflineApp;

use BeneficiaryBundle\Entity\Beneficiary;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Controller\AbstractController;
use NewApiBundle\InputType\BeneficiaryFilterInputType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BeneficiaryController extends AbstractController
{

    /**
     * @Rest\Get("/offline-app/v2/beneficiaries")
     *
     * @param Request                    $request
     * @param BeneficiaryFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function beneficiaries(Request $request, BeneficiaryFilterInputType $filter): JsonResponse
    {
        $beneficiaries = $this->getDoctrine()->getRepository(Beneficiary::class)->findByParams($filter);

        $response = $this->json($beneficiaries);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }
}
