<?php

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Utils\AssistanceBeneficiaryService;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\AddBeneficiaryToAssistanceInputType;
use NewApiBundle\InputType\AddHouseholdsToProjectInputType;
use NewApiBundle\InputType\AssistanceCreateInputType;
use NewApiBundle\InputType\BeneficiaryFilterInputType;
use NewApiBundle\InputType\BeneficiaryOrderInputType;
use NewApiBundle\InputType\NationalIdFilterInputType;
use NewApiBundle\InputType\PhoneFilterInputType;
use NewApiBundle\InputType\RemoveBeneficiaryFromAssistanceInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BeneficiaryController extends AbstractController
{
    /**
     * @Rest\Post("/assistances/beneficiaries")
     *
     * @param AssistanceCreateInputType $inputType
     * @param Pagination $paginationF
     *
     * @return JsonResponse
     */
    public function precalculateBeneficiaries(AssistanceCreateInputType $inputType, Pagination $pagination): JsonResponse
    {
        $beneficiaries = $this->get('distribution.assistance_service')->findByCriteria($inputType, $pagination);

        return $this->json($beneficiaries);
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

        /** @var AssistanceBeneficiaryService $assistanceBeneficiaryService */
        $assistanceBeneficiaryService = $this->get('distribution.assistance_beneficiary_service');
        $assistanceBeneficiaryService->addBeneficiaries($assistance, $data);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Delete("/assistances/{id}/beneficiaries")
     *
     * @param Assistance                               $assistance
     * @param RemoveBeneficiaryFromAssistanceInputType $inputType
     *
     * @return JsonResponse
     */
    public function removeBeneficiariesFromAssistance(Assistance $assistance, RemoveBeneficiaryFromAssistanceInputType $inputType): JsonResponse
    {
        /** @var AssistanceBeneficiaryService $assistanceBeneficiaryService */
        $assistanceBeneficiaryService = $this->get('distribution.assistance_beneficiary_service');

        foreach ($inputType->getBeneficiaryIds() as $id) {
            $beneficiary = $this->getDoctrine()->getRepository(Beneficiary::class)->find($id);
            $assistanceBeneficiaryService->removeBeneficiaryInDistribution(
                $assistance,
                $beneficiary,
                ['justification' => $inputType->getJustification()]
            );
        }

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

    /**
     * @Rest\Post("/projects/{id}/beneficiaries")
     *
     * @param Project                         $project
     *
     * @param AddHouseholdsToProjectInputType $inputType
     *
     * @return JsonResponse
     */
    public function addBeneficiariesToProject(Project $project, AddHouseholdsToProjectInputType $inputType): JsonResponse
    {
        $this->get('project.project_service')->addHouseholds($project, $inputType);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
