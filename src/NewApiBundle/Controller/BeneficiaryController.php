<?php

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use CommonBundle\Controller\ExportController;
use CommonBundle\Entity\Organization;
use CommonBundle\Pagination\Paginator;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Enum\AssistanceTargetType;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Enum\PersonGender;
use NewApiBundle\InputType\AssistanceCreateInputType;
use NewApiBundle\InputType\BenefciaryPatchInputType;
use NewApiBundle\InputType\BeneficiaryExportFilterInputType;
use NewApiBundle\InputType\BeneficiaryFilterInputType;
use NewApiBundle\InputType\BeneficiarySelectedFilterInputType;
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
     * @Rest\Post("/web-app/v1/assistances/beneficiaries")
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
     * @Rest\Post("/web-app/v1/assistances/vulnerability-scores")
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
     * @Rest\Get("/web-app/v1/beneficiaries/exports")
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
                    'gender' => PersonGender::valueToAPI($bnf->getGender()),
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
     * @Rest\Get("/web-app/v1/assistances/{id}/beneficiaries/exports")
     *
     * @param Assistance $assistance
     * @param Request    $request
     *
     * @return Response
     */
    public function exportsByAssistance(Assistance $assistance, Request $request): Response
    {
        $organization = $this->getDoctrine()->getRepository(Organization::class)->findOneBy([]);
        $type = $request->query->get('type');

        $filename = $this->get('export.spreadsheet')->export($assistance, $organization, $type);

        try {
            // Create binary file to send
            $response = new BinaryFileResponse(getcwd() . '/' . $filename);

            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
            $mimeTypeGuesser = new \Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser();
            if ($mimeTypeGuesser->isSupported()) {
                $response->headers->set('Content-Type', $mimeTypeGuesser->guess(getcwd() . '/' . $filename));
            } else {
                $response->headers->set('Content-Type', 'text/plain');
            }
            $response->deleteFileAfterSend(true);

            return $response;
        } catch (\Exception $exception) {
            return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/beneficiaries/exports-raw")
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
     * @Rest\Get("/web-app/v1/beneficiaries/national-ids")
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
     * @Rest\Get("/web-app/v1/beneficiaries/national-ids/{id}")
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
     * @Rest\Get("/web-app/v1/beneficiaries/phones")
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
     * @Rest\Get("/web-app/v1/beneficiaries/phones/{id}")
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
     * @Rest\Get("/web-app/v1/beneficiaries/{id}")
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
     * @Rest\Patch("/web-app/v1/beneficiaries/{id}")
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
     * @Rest\Get("/web-app/v1/beneficiaries")
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
     * @Rest\Get("/web-app/v1/projects/{id}/targets/{target}/beneficiaries")
     *
     * @param Project $project
     * @param string $target
     * @param BeneficiarySelectedFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function getBeneficiaries(Project $project, string $target, BeneficiarySelectedFilterInputType $filter): JsonResponse
    {
        if (!in_array($target, AssistanceTargetType::values())){
            throw $this->createNotFoundException('Invalid target. Allowed are '.implode(', ', AssistanceTargetType::values()));
        }

        $assistanceId = $filter->getId();
        if ($assistanceId) {
            $excludedAssistance = $this->getDoctrine()->getRepository(Assistance::class)->find($assistanceId);
            $beneficiaries = $this->getDoctrine()->getRepository(Beneficiary::class)->getNotSelectedBeneficiariesOfProject($project->getId(), $target, $excludedAssistance);
        } else {
            $beneficiaries = $this->getDoctrine()->getRepository(Beneficiary::class)->getAllOfProject($project->getId(), $target);
        }


        return $this->json(new Paginator($beneficiaries));
    }
}
