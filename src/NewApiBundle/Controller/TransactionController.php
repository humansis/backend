<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Pagination\Paginator;
use DistributionBundle\Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Codelist\CodeLists;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use TransactionBundle\Entity\Transaction;

class TransactionController extends AbstractController
{
    /**
     * @Rest\Get("/assistances/{assistanceId}/beneficiaries/{beneficiaryId}/transactions")
     * @ParamConverter("assistance", options={"mapping": {"assistanceId" : "id"}})
     * @ParamConverter("beneficiary", options={"mapping": {"beneficiaryId" : "id"}})
     *
     * @param Assistance  $assistance
     * @param Beneficiary $beneficiary
     *
     * @return JsonResponse
     */
    public function byAssistanceAndBeneficiary(Assistance $assistance, Beneficiary $beneficiary): JsonResponse
    {
        $list = $this->getDoctrine()->getRepository(Transaction::class)
            ->findByAssistanceBeneficiary($assistance, $beneficiary);

        return $this->json(new Paginator($list));
    }

    /**
     * @Rest\Get("/transactions/statuses")
     *
     * @return JsonResponse
     */
    public function statuses(): JsonResponse
    {
        $data = CodeLists::mapArray(Transaction::statuses());

        return $this->json(new Paginator($data));
    }
}
