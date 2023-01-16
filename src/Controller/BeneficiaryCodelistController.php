<?php

declare(strict_types=1);

namespace Controller;

use Entity\Referral;
use Enum\ResidencyStatus;
use Enum\VulnerabilityCriteria;
use Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use Enum\BeneficiaryType;
use Enum\NationalIdType;
use Enum\PhoneTypes;
use Services\CodeListService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Cache(expires: '+12 hours', public: true)]
class BeneficiaryCodelistController extends AbstractController
{
    public function __construct(private readonly CodeListService $codeListService)
    {
    }

    #[Rest\Get('/web-app/v1/beneficiaries/types')]
    public function getTypes(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(BeneficiaryType::values());

        return $this->json(new Paginator($data));
    }

    #[Rest\Get('/web-app/v1/beneficiaries/referral-types')]
    public function getReferralTypes(): JsonResponse
    {
        $data = $this->codeListService->mapArray(Referral::REFERRALTYPES);

        return $this->json(new Paginator($data));
    }

    #[Rest\Get('/web-app/v1/beneficiaries/residency-statuses')]
    public function getResidencyStatuses(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(ResidencyStatus::values());

        return $this->json(new Paginator($data));
    }

    #[Rest\Get('/web-app/v1/beneficiaries/vulnerability-criteria')]
    public function getVulnerabilityCriterion(): JsonResponse
    {

        $data = $this->codeListService->mapEnum(VulnerabilityCriteria::all());

        return $this->json(new Paginator($data));
    }

    #[Rest\Get('/web-app/v1/beneficiaries/national-ids/types')]
    public function getNationalIdTypes(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(NationalIdType::values());

        return $this->json(new Paginator($data));
    }

    #[Rest\Get('/web-app/v1/beneficiaries/phones/types')]
    public function getPhoneTypes(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(PhoneTypes::values());

        return $this->json(new Paginator($data));
    }
}
