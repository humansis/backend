<?php

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Enum\ResidencyStatus;
use CommonBundle\Pagination\Paginator;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Enum\PhoneTypes;
use NewApiBundle\Utils\CodeLists;
use Symfony\Component\HttpFoundation\JsonResponse;

class BeneficiaryCodelistController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Rest\Get("/beneficiaries/residency-statuses")
     *
     * @return JsonResponse
     */
    public function getResidencyStatuses(): JsonResponse
    {
        $data = CodeLists::mapEnum(ResidencyStatus::all());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/beneficiaries/vulnerability-criteria")
     *
     * @return JsonResponse
     */
    public function getVulnerabilityCriterion(): JsonResponse
    {
        $criterion = $this->entityManager->getRepository(VulnerabilityCriterion::class)
            ->findAllActive();

        return $this->json(new Paginator(CodeLists::mapCriterion($criterion)));
    }

    /**
     * @Rest\Get("/beneficiaries/national-ids/types")
     *
     * @return JsonResponse
     */
    public function getNationalIdTypes(): JsonResponse
    {
        $data = CodeLists::mapEnum(NationalId::types());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/beneficiaries/phones/types")
     *
     * @return JsonResponse
     */
    public function getPhoneTypes(): JsonResponse
    {
        $data = CodeLists::mapEnum(PhoneTypes::values());

        return $this->json(new Paginator($data));
    }
}
