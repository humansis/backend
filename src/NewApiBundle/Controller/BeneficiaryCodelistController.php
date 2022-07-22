<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Referral;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Enum\ResidencyStatus;
use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Codelist\CodeLists;
use NewApiBundle\Enum\BeneficiaryType;
use NewApiBundle\Enum\Domain;
use NewApiBundle\Enum\NationalIdType;
use NewApiBundle\Enum\PhoneTypes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Cache(expires="+5 days", public=true)
 */
class BeneficiaryCodelistController extends AbstractController
{
    /** @var TranslatorInterface */
    private $translator;
    
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Rest\Get("/web-app/v1/beneficiaries/types")
     *
     * @return JsonResponse
     */
    public function getTypes(): JsonResponse
    {
        $data = CodeLists::mapEnum(BeneficiaryType::values(), $this->translator);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/beneficiaries/referral-types")
     *
     * @return JsonResponse
     */
    public function getReferralTypes(): JsonResponse
    {
        $data = CodeLists::mapArray(Referral::REFERRALTYPES, $this->translator, Domain::SECTORS);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/beneficiaries/residency-statuses")
     *
     * @return JsonResponse
     */
    public function getResidencyStatuses(): JsonResponse
    {
        $data = CodeLists::mapEnum(ResidencyStatus::values(), $this->translator, Domain::ENUMS);

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

        return $this->json(new Paginator(CodeLists::mapCriterion($criterion, $this->translator)));
    }

    /**
     * @Rest\Get("/web-app/v1/beneficiaries/national-ids/types")
     *
     * @return JsonResponse
     */
    public function getNationalIdTypes(): JsonResponse
    {
        $data = CodeLists::mapEnum(NationalIdType::values(), $this->translator, Domain::ENUMS);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/beneficiaries/phones/types")
     *
     * @return JsonResponse
     */
    public function getPhoneTypes(): JsonResponse
    {
        $data = CodeLists::mapEnum(PhoneTypes::values(), $this->translator, Domain::ENUMS);

        return $this->json(new Paginator($data));
    }
}
