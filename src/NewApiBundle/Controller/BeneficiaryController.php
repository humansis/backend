<?php

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\HouseholdLocation;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Utils\AssistanceBeneficiaryService;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\AddBeneficiaryToAssistanceInputType;
use NewApiBundle\InputType\BeneficiaryFilterInputType;
use NewApiBundle\InputType\BeneficiaryOrderInputType;
use NewApiBundle\InputType\CampAddressFilterInputType;
use NewApiBundle\InputType\NationalIdFilterInputType;
use NewApiBundle\InputType\PhoneFilterInputType;
use NewApiBundle\InputType\ResidenceAddressFilterInputType;
use NewApiBundle\InputType\TemporarySettlementAddressFilterInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BeneficiaryController extends AbstractController
{
    /** @var AssistanceBeneficiaryService */
    private $assistanceBeneficiaryService;

    /**
     * BeneficiaryController constructor.
     *
     * @param AssistanceBeneficiaryService $assistanceBeneficiaryService
     */
    public function __construct(AssistanceBeneficiaryService $assistanceBeneficiaryService)
    {
        $this->assistanceBeneficiaryService = $assistanceBeneficiaryService;
    }

    /**
     * @Rest\Get("/assistances/{id}/beneficiaries")
     *
     * @param Assistance                 $assistance
     * @param BeneficiaryFilterInputType $filter
     * @param BeneficiaryOrderInputType  $orderBy
     * @param Pagination                 $pagination
     *
     * @return JsonResponse
     */
    public function beneficiariesByAssistance(
        Assistance $assistance,
        BeneficiaryFilterInputType $filter,
        BeneficiaryOrderInputType $orderBy,
        Pagination $pagination
    ): JsonResponse
    {
        if ($assistance->getArchived()) {
            throw $this->createNotFoundException();
        }

        $beneficiaries = $this->getDoctrine()->getRepository(Beneficiary::class)->findByAssistance($assistance, $filter, $orderBy, $pagination);

        return $this->json($beneficiaries);
    }

    /**
     * @Rest\Put("/assistances/{id}/beneficiaries")
     *
     * @param Assistance                          $assistance
     * @param AddBeneficiaryToAssistanceInputType $inputType
     *
     * @return JsonResponse
     */
    public function addBeneficiaryToAssistance(Assistance $assistance, AddBeneficiaryToAssistanceInputType $inputType): JsonResponse
    {
        $data = ['beneficiaries' => [], 'justification' => $inputType->getJustification()];
        foreach ($inputType->getBeneficiaryIds() as $id) {
            $data['beneficiaries'][] = ['id' => $id];
        }

        $this->assistanceBeneficiaryService->addBeneficiaries($assistance, $data);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Get("/beneficiaries/national-ids")
     *
     * @param NationalIdFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function nationalIds(NationalIdFilterInputType $filter): JsonResponse
    {
        $nationalIds = $this->getDoctrine()->getRepository(NationalId::class)->findByParams($filter);

        return $this->json($nationalIds);
    }

    /**
     * @Rest\Get("/beneficiaries/national-ids/{id}")
     *
     * @param NationalId $nationalId
     *
     * @return JsonResponse
     */
    public function nationalId(NationalId $nationalId): JsonResponse
    {
        return $this->json($nationalId);
    }

    /**
     * @Rest\Get("/beneficiaries/phones")
     *
     * @param PhoneFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function phones(PhoneFilterInputType $filter): JsonResponse
    {
        $params = $this->getDoctrine()->getRepository(Phone::class)->findByParams($filter);

        return $this->json($params);
    }

    /**
     * @Rest\Get("/beneficiaries/phones/{id}")
     *
     * @param Phone $phone
     *
     * @return JsonResponse
     */
    public function phone(Phone $phone): JsonResponse
    {
        return $this->json($phone);
    }

    /**
     * @Rest\Get("/beneficiaries/addresses/camps")
     *
     * @param CampAddressFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function camps(CampAddressFilterInputType $filter): JsonResponse
    {
        $campAddresses = $this->getDoctrine()->getRepository(HouseholdLocation::class)->findCampAddressesByParams($filter);

        return $this->json($campAddresses);
    }

    /**
     * @Rest\Get("/beneficiaries/addresses/camps/{id}")
     *
     * @param HouseholdLocation $campAddress
     *
     * @return JsonResponse
     */
    public function camp(HouseholdLocation $campAddress): JsonResponse
    {
        if (HouseholdLocation::LOCATION_TYPE_CAMP !== $campAddress->getType()) {
            throw $this->createNotFoundException();
        }

        return $this->json($campAddress);
    }

    /**
     * @Rest\Get("/beneficiaries/addresses/residencies")
     *
     * @param ResidenceAddressFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function residences(ResidenceAddressFilterInputType $filter): JsonResponse
    {
        $residences = $this->getDoctrine()->getRepository(HouseholdLocation::class)->findResidenciesByParams($filter);

        return $this->json($residences);
    }

    /**
     * @Rest\Get("/beneficiaries/addresses/residencies/{id}")
     *
     * @param HouseholdLocation $residence
     *
     * @return JsonResponse
     */
    public function residence(HouseholdLocation $residence): JsonResponse
    {
        if (HouseholdLocation::LOCATION_TYPE_RESIDENCE !== $residence->getType()) {
            throw $this->createNotFoundException();
        }

        return $this->json($residence);
    }

    /**
     * @Rest\Get("/beneficiaries/addresses/temporary-settlements")
     *
     * @param TemporarySettlementAddressFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function temporarySettlements(TemporarySettlementAddressFilterInputType $filter): JsonResponse
    {
        $temporarySettlements = $this->getDoctrine()->getRepository(HouseholdLocation::class)->findTemporarySettlementsByParams($filter);

        return $this->json($temporarySettlements);
    }

    /**
     * @Rest\Get("/beneficiaries/addresses/temporary-settlements/{id}")
     *
     * @param HouseholdLocation $temporarySettlement
     *
     * @return JsonResponse
     */
    public function temporarySettlement(HouseholdLocation $temporarySettlement): JsonResponse
    {
        if (HouseholdLocation::LOCATION_TYPE_SETTLEMENT !== $temporarySettlement->getType()) {
            throw $this->createNotFoundException();
        }

        return $this->json($temporarySettlement);
    }

    /**
     * @Rest\Get("/beneficiaries/{id}")
     *
     * @param Beneficiary $beneficiary
     *
     * @return JsonResponse
     */
    public function beneficiary(Beneficiary $beneficiary): JsonResponse
    {
        if ($beneficiary->getArchived()) {
            throw $this->createNotFoundException();
        }

        return $this->json($beneficiary);
    }

    /**
     * @Rest\Get("/beneficiaries")
     *
     * @param BeneficiaryFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function beneficiaryies(BeneficiaryFilterInputType $filter): JsonResponse
    {
        $beneficiaries = $this->getDoctrine()->getRepository(Beneficiary::class)->findByParams($filter);

        return $this->json($beneficiaries);
    }
}
