<?php

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Utils\AssistanceBeneficiaryService;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\AddBeneficiaryToAssistanceInputType;
use NewApiBundle\InputType\Beneficiary\ProjectBeneficiariesFilterInputType;
use NewApiBundle\InputType\BeneficiaryFilterInputType;
use NewApiBundle\InputType\BeneficiaryOrderInputType;
use NewApiBundle\InputType\NationalIdFilterInputType;
use NewApiBundle\InputType\PhoneFilterInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BeneficiaryController extends AbstractController
{
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

        /** @var AssistanceBeneficiaryService $assistanceBeneficiaryService */
        $assistanceBeneficiaryService = $this->get('distribution.assistance_beneficiary_service');
        $assistanceBeneficiaryService->addBeneficiaries($assistance, $data);

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

    /**
     * @Rest\Get("/projects/{id}/beneficiaries")
     *
     * @param Project $project
     *
     * @return JsonResponse
     */
    public function getBeneficiaries(Project $project): JsonResponse
    {
        $beneficiaries = $this->getDoctrine()->getRepository(Beneficiary::class)->findByProject($project);

        return $this->json($beneficiaries);
    }
}
