<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use NewApiBundle\Entity\NationalId;
use NewApiBundle\Entity\Referral;
use NewApiBundle\Entity\VulnerabilityCriterion;
use NewApiBundle\Enum\ResidencyStatus;
use BeneficiaryBundle\Entity\Referral;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Enum\ResidencyStatus;
use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Enum\BeneficiaryType;
use NewApiBundle\Enum\Domain;
use NewApiBundle\Enum\NationalIdType;
use NewApiBundle\Enum\PhoneTypes;
use NewApiBundle\Services\CodeListService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Cache(expires="+5 days", public=true)
 */
class BeneficiaryCodelistController extends AbstractController
{
    /** @var CodeListService */
    private $codeListService;
    
    public function __construct(CodeListService $codeListService)
    {
        $this->codeListService = $codeListService;
    }

    /**
     * @Rest\Get("/web-app/v1/beneficiaries/types")
     *
     * @return JsonResponse
     */
    public function getTypes(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(BeneficiaryType::values());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/beneficiaries/referral-types")
     *
     * @return JsonResponse
     */
    public function getReferralTypes(): JsonResponse
    {
        $data = $this->codeListService->mapArray(Referral::REFERRALTYPES, Domain::SECTORS);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/beneficiaries/residency-statuses")
     *
     * @return JsonResponse
     */
    public function getResidencyStatuses(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(ResidencyStatus::values(), Domain::ENUMS);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/beneficiaries/vulnerability-criteria")
     *
     * @return JsonResponse
     */
    public function getVulnerabilityCriterion(): JsonResponse
    {
        $criterion = $this->getDoctrine()->getRepository(VulnerabilityCriterion::class)
            ->findAllActive();

        return $this->json(new Paginator($this->codeListService->mapCriterion($criterion)));
    }

    /**
     * @Rest\Get("/web-app/v1/beneficiaries/national-ids/types")
     *
     * @return JsonResponse
     */
    public function getNationalIdTypes(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(NationalIdType::values(), Domain::ENUMS);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/beneficiaries/phones/types")
     *
     * @return JsonResponse
     */
    public function getPhoneTypes(): JsonResponse
    {
        $data = $this->codeListService->mapEnum(PhoneTypes::values(), Domain::ENUMS);

        return $this->json(new Paginator($data));
    }
}
