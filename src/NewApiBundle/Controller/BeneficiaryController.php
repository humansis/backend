<?php

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use CommonBundle\Controller\ExportController;
use CommonBundle\Pagination\Paginator;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Enum\AssistanceTargetType;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\AssistanceCreateInputType;
use NewApiBundle\InputType\BenefciaryPatchInputType;
use NewApiBundle\InputType\BeneficiaryExportFilterInputType;
use NewApiBundle\InputType\BeneficiaryFilterInputType;
use NewApiBundle\InputType\NationalIdFilterInputType;
use NewApiBundle\InputType\PhoneFilterInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;

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
     * @Rest\Post("/assistances/vulnerability-scores")
     *
     * @param AssistanceCreateInputType $inputType
     * @param Pagination $pagination
     *
     * @return JsonResponse
     */
    public function vulnerabilityScores(AssistanceCreateInputType $inputType, Pagination $pagination): JsonResponse
    {
        $vulnerabilities = $this->get('distribution.assistance_service')->findVulnerabilityScores($inputType, $pagination);

        return $this->json($vulnerabilities);
    }

    /**
     * @Rest\Get("/beneficiaries/exports")
     *
     * @param Request                          $request
     * @param BeneficiaryExportFilterInputType $inputType
     *
     * @return Response
     */
    public function exports(Request $request, BeneficiaryExportFilterInputType $inputType): Response
    {
        $sample = [];
        if ($inputType->hasIds()) {
            foreach ($inputType->getIds() as $id) {
                $bnf = $this->getDoctrine()->getRepository(Beneficiary::class)->find($id);
                if (!$bnf) {
                    throw new \Doctrine\ORM\EntityNotFoundException('Beneficiary with ID #'.$id.' does not exists.');
                }

                $sample[] = [
                    'gender' => (string) $bnf->getGender(),
                    'en_given_name' => $bnf->getEnGivenName(),
                    'en_family_name' => $bnf->getEnFamilyName(),
                    'local_given_name' => $bnf->getLocalGivenName(),
                    'local_family_name' => $bnf->getLocalFamilyName(),
                    'status' => (string) $bnf->getStatus(),
                    'residency_status' => $bnf->getResidencyStatus(),
                    'date_of_birth' => $bnf->getDateOfBirth(),
                ];
            }
        }

        $request->query->add(['distributionSample' => true]);
        $request->request->add(['sample' => $sample]);

        return $this->forward(ExportController::class.'::exportAction', [], $request->query->all());
    }

    /**
     * @Rest\Get("/assistances/{id}/beneficiaries/exports")
     *
     * @param Assistance $assistance
     * @param Request    $request
     *
     * @return Response
     */
    public function exportsByAssistance(Assistance $assistance, Request $request): Response
    {
        $request->query->add(['beneficiariesInDistribution' => $assistance->getId()]);

        return $this->forward(ExportController::class.'::exportAction', [], $request->query->all());
    }

    /**
     * @Rest\Get("/assistances/{id}/beneficiaries/exports-raw")
     *
     * @param Assistance $assistance
     * @param Request    $request
     *
     * @return Response
     */
    public function exportsByAssistanceRaw(Assistance $assistance, Request $request): Response
    {
        $file = $this->get('distribution.assistance_service')->exportGeneralReliefDistributionToCsv($assistance, $request->query->get('type'));

        $response = new BinaryFileResponse(getcwd() . '/' . $file);

        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file);
        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
        if ($mimeTypeGuesser->isGuesserSupported()) {
            $response->headers->set('Content-Type', $mimeTypeGuesser->guessMimeType(getcwd() . '/' . $file));
        } else {
            $response->headers->set('Content-Type', 'text/plain');
        }
        $response->deleteFileAfterSend(true);

        return $response;
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
     * @Rest\Patch("/beneficiaries/{id}")
     *
     * @param Beneficiary              $beneficiary
     * @param BenefciaryPatchInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(Beneficiary $beneficiary, BenefciaryPatchInputType $inputType): JsonResponse
    {
        if ($beneficiary->getArchived()) {
            throw $this->createNotFoundException();
        }

        $this->get('beneficiary.beneficiary_service')->patch($beneficiary, $inputType);

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
     * @Rest\Get("/projects/{id}/targets/{target}/beneficiaries")
     *
     * @param Project $project
     * @param string  $target
     *
     * @return JsonResponse
     */
    public function getBeneficiaries(Project $project, string $target): JsonResponse
    {
        if (!in_array($target, AssistanceTargetType::values())){
            throw $this->createNotFoundException('Invalid target. Allowed are '.implode(', ', AssistanceTargetType::values()));
        }

        $beneficiaries = $this->getDoctrine()->getRepository(Beneficiary::class)->getAllOfProject($project->getId(), $target);

        return $this->json(new Paginator($beneficiaries));
    }
}
