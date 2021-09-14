<?php

namespace NewApiBundle\Controller\OfflineApp;

use BeneficiaryBundle\Entity\Beneficiary;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Controller\AbstractController;
use NewApiBundle\InputType\BeneficiaryFilterInputType;
use Symfony\Component\HttpFoundation\JsonResponse;

class BeneficiaryController extends AbstractController
{

    /**
     * @Rest\Get("/offline-app/v1/beneficiaries")
     *
     * @param BeneficiaryFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function beneficiaries(BeneficiaryFilterInputType $filter): JsonResponse
    {
        $beneficiaries = $this->getDoctrine()->getRepository(Beneficiary::class)->findByParams($filter);

        return $this->json($beneficiaries);
    }
}
