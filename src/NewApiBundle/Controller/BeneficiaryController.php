<?php

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;

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

        return $this->legacyExport($request);
    }

    /**
     * @deprecated copied from old ExportController, needs to be rewritten
     * @param Request $request
     *
     * @return Response
     */
    private function legacyExport(Request $request): Response
    {
        try {
            set_time_limit(600);
            // Format of the file (csv, xlsx, ods, pdf)
            $type = $request->query->get('type');
            // Generate corresponding file depending on request
            if ($request->query->get('distributions')) {
                $idProject = $request->query->get('distributions');
                if ($type === 'pdf') {
                    return $this->get('distribution.assistance_service')->exportToPdf($idProject);
                }
                $filename = $this->get('distribution.assistance_service')->exportToCsv($idProject, $type);
            } elseif ($request->query->get('officialDistributions')) {
                $idProject = $request->query->get('officialDistributions');
                if ($type === 'pdf') {
                    return $this->get('distribution.assistance_service')->exportToPdf($idProject);
                }
                $filename = $this->get('distribution.assistance_service')->exportToOfficialCsv($idProject, $type);
            } elseif ($request->query->get('beneficiaries')) {
                $countryIso3 = $request->request->get("__country");
                $filters = $request->request->get('filters');
                $ids = $request->request->get('ids');
                $filename = $this->get('beneficiary.beneficiary_service')->exportToCsvDeprecated($type, $countryIso3, $filters, $ids);
            } elseif ($request->query->get('users')) {
                $filename = $this->get('user.user_service')->exportToCsv($type);
            } elseif ($request->query->get('countries')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->get('beneficiary.country_specific_service')->exportToCsv($type, $countryIso3);
            } elseif ($request->query->get('donors')) {
                $filename = $this->get('project.donor_service')->exportToCsv($type);
            } elseif ($request->query->get('projects')) {
                $country = $request->query->get('projects');
                $filename = $this->get('project.project_service')->exportToCsv($country, $type);
            } elseif ($request->query->get('distributionSample')) {
                $arrayObjectBeneficiary = $request->request->get('sample');
                $filename = $this->get('distribution.assistance_beneficiary_service')->exportToCsv($arrayObjectBeneficiary, $type);
            } elseif ($request->query->get('householdsTemplate')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->get('beneficiary.household_export_csv_service')->exportToCsv($type, $countryIso3);
            } elseif ($request->query->get('transactionDistribution') ||
                $request->query->get('smartcardDistribution') ||
                $request->query->get('voucherDistribution') ||
                $request->query->get('generalreliefDistribution') ||
                $request->query->get('beneficiariesInDistribution')) {
                $idDistribution = $request->query->get('transactionDistribution') ??
                    $request->query->get('smartcardDistribution') ??
                    $request->query->get('voucherDistribution') ??
                    $request->query->get('generalreliefDistribution') ??
                    $request->query->get('beneficiariesInDistribution');
                $distribution = $this->get('distribution.assistance_service')->findOneById($idDistribution);
                // todo find organisation by relation to distribution
                $organization = $this->getDoctrine()->getRepository(Organization::class)->findOneBy([]);
                if ($type === 'pdf') {
                    return $this->get('export.pdf')->export($distribution, $organization);
                }
                $filename = $this->get('export.spreadsheet')->export($distribution, $organization, $type);
                // raw export for legacy purpose
                if ($type === 'xlsx' && in_array($distribution->getTargetType(), [AssistanceTargetType::HOUSEHOLD, AssistanceTargetType::INDIVIDUAL])) { // hack to enable raw export, will be forgotten with FE switch
                    if ($request->query->has('transactionDistribution')) {
                        $filename = $this->get('transaction.transaction_service')->exportToCsv($distribution, 'xlsx');
                    }
                    if ($request->query->has('smartcardDistribution')) {
                        // no change
                    }
                    if ($request->query->has('voucherDistribution')) {
                        $filename = $this->get('distribution.assistance_service')->exportVouchersDistributionToCsv($distribution, $type);
                    }
                    if ($request->query->has('generalreliefDistribution')) {
                        $filename = $this->get('distribution.assistance_service')->exportGeneralReliefDistributionToCsv($distribution, 'xlsx');
                    }
                    if ($request->query->has('beneficiariesInDistribution')) {
                        $filename = $this->get('distribution.assistance_service')->exportToCsvBeneficiariesInDistribution($distribution, $type);
                    }
                }
            } elseif ($request->query->get('bookletCodes')) {
                $ids = $request->request->get('ids');
                $countryIso3 = $request->request->get("__country");
                $filters = $request->request->get('filters');
                if ($type === 'pdf') {
                    return $this->get('voucher.voucher_service')->exportToPdf($ids, $countryIso3, $filters);
                }
                if ($type === 'csv') {
                    return $this->get('voucher.voucher_service')->exportToCsv($type, $countryIso3, $ids, $filters);
                }
                $filename = $this->get('voucher.voucher_service')->exportToCsv($type, $countryIso3, $ids, $filters);
            } elseif ($request->query->get('reporting')) {
                $filename = $this->get('reporting.reporting_service')->exportToCsv($request->request, $type);
            } elseif ($request->query->get('products')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->get('voucher.product_service')->exportToCsv($type, $countryIso3);
            } elseif ($request->query->get('vendors')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->get('voucher.vendor_service')->exportToCsv($type, $countryIso3);
            } else {
                return new JsonResponse('No export selected', Response::HTTP_BAD_REQUEST);
            }

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
