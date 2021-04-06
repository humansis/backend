<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Pagination\Paginator;
use DistributionBundle\Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use VoucherBundle\Entity\SmartcardDeposit;

class SmartcardController extends AbstractController
{
    /**
     * @Rest\Get("/assistances/{assistanceId}/beneficiaries/{beneficiaryId}/smartcard-deposits")
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
        $list = $this->getDoctrine()->getRepository(SmartcardDeposit::class)
            ->findByAssistanceBeneficiary($assistance, $beneficiary);

        return $this->json(new Paginator($list));
    }
}
