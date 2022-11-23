<?php

namespace Controller;

use Doctrine\ORM\Exception\ORMException;
use Entity\Beneficiary;
use Entity\NationalId;
use Entity\Phone;
use Exception;
use Exception\CsvParserException;
use Repository\BeneficiaryRepository;
use Repository\NationalIdRepository;
use Repository\OrganizationRepository;
use Repository\PhoneRepository;
use Utils\BeneficiaryService;
use Pagination\Paginator;
use Entity\Assistance;
use Enum\AssistanceTargetType;
use Repository\AssistanceRepository;
use Utils\AssistanceService;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Enum\EnumApiValueNoFoundException;
use Enum\PersonGender;
use InputType\Assistance\Scoring\ScoringService;
use InputType\AssistanceCreateInputType;
use InputType\BenefciaryPatchInputType;
use InputType\BeneficiaryExportFilterInputType;
use InputType\BeneficiaryFilterInputType;
use InputType\BeneficiarySelectedFilterInputType;
use InputType\NationalIdFilterInputType;
use InputType\PhoneFilterInputType;
use InputType\VulnerabilityScoreInputType;
use Request\Pagination;
use Entity\Project;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;
use Export\AssistanceSpreadsheetExport;

class BeneficiaryController extends AbstractController
{
    public function __construct(private readonly AssistanceSpreadsheetExport $assistanceSpreadsheetExport, private readonly AssistanceService $assistanceService, private readonly BeneficiaryService $beneficiaryService, private readonly ScoringService $scoringService)
    {
    }

    /**
     * @Rest\Post("/web-app/v1/assistances/beneficiaries")
     *
     *
     * @throws EntityNotFoundException
     */
    public function precalculateBeneficiaries(
        AssistanceCreateInputType $inputType,
        Pagination $pagination
    ): JsonResponse {
        $beneficiaries = $this->assistanceService->findByCriteria($inputType, $pagination);

        return $this->json($beneficiaries);
    }

    /**
     * @Rest\Post("/web-app/v1/assistances/vulnerability-scores")
     *
     *
     * @throws EntityNotFoundException
     * @throws CsvParserException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    public function vulnerabilityScoresOld(AssistanceCreateInputType $inputType, Pagination $pagination): JsonResponse
    {
        $vulnerabilities = $this->assistanceService->findVulnerabilityScores($inputType, $pagination);

        return $this->json($vulnerabilities);
    }

    /**
     * @Rest\Post("/web-app/v2/assistances/vulnerability-scores")
     */
    public function vulnerabilityScores(
        VulnerabilityScoreInputType $vulnerabilityScoreInputType,
        Request $request
    ): JsonResponse {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        $scoring = $this->scoringService->computeTotalScore(
            $vulnerabilityScoreInputType,
            $request->headers->get('country')
        );

        return $this->json(new Paginator($scoring));
    }

    /**
     * @Rest\Get("/web-app/v1/beneficiaries/exports")
     *
     *
     * @throws EntityNotFoundException
     * @throws EnumApiValueNoFoundException
     */
    public function exports(
        Request $request,
        BeneficiaryExportFilterInputType $inputType,
        BeneficiaryRepository $beneficiaryRepository
    ): Response {
        $sample = [];
        if ($inputType->hasIds()) {
            foreach ($inputType->getIds() as $id) {
                $bnf = $beneficiaryRepository->find($id);
                if (!$bnf) {
                    throw new EntityNotFoundException('Beneficiary with ID #' . $id . ' does not exists.');
                }

                $sample[] = [
                    'gender' => PersonGender::valueToAPI($bnf->getPerson()->getGender()),
                    'en_given_name' => $bnf->getPerson()->getEnGivenName(),
                    'en_family_name' => $bnf->getPerson()->getEnFamilyName(),
                    'local_given_name' => $bnf->getPerson()->getLocalGivenName(),
                    'local_family_name' => $bnf->getPerson()->getLocalFamilyName(),
                    'status' => (string) $bnf->isHead(),
                    'residency_status' => $bnf->getResidencyStatus(),
                    'date_of_birth' => $bnf->getPerson()->getDateOfBirth(),
                ];
            }
        }

        $request->query->add(['distributionSample' => true]);
        $request->request->add(['sample' => $sample]);

        return $this->forward(ExportController::class . '::exportAction', [], $request->query->all());
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/beneficiaries/exports")
     *
     *
     */
    public function exportsByAssistance(
        Assistance $assistance,
        Request $request,
        OrganizationRepository $organizationRepository
    ): Response {
        $organization = $organizationRepository->findOneBy([]);
        $type = $request->query->get('type');

        $filename = $this->assistanceSpreadsheetExport->export($assistance, $organization, $type);

        try {
            // Create binary file to send
            $response = new BinaryFileResponse(getcwd() . '/' . $filename);

            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
            $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
            if ($mimeTypeGuesser->isGuesserSupported()) {
                $response->headers->set('Content-Type', $mimeTypeGuesser->guessMimeType(getcwd() . '/' . $filename));
            } else {
                $response->headers->set('Content-Type', 'text/plain');
            }
            $response->deleteFileAfterSend(true);

            return $response;
        } catch (Exception $exception) {
            return new JsonResponse(
                $exception->getMessage(),
                $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/beneficiaries/exports-raw")
     *
     *
     */
    public function exportsByAssistanceRaw(Assistance $assistance, Request $request): Response
    {
        $file = $this->assistanceService->exportGeneralReliefDistributionToCsv(
            $assistance,
            $request->query->get('type')
        );

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
     *
     */
    public function nationalIds(
        NationalIdFilterInputType $filter,
        NationalIdRepository $nationalIdRepository
    ): JsonResponse {
        $nationalIds = $nationalIdRepository->findByParams($filter);

        return $this->json($nationalIds);
    }

    /**
     * @Rest\Get("/web-app/v1/beneficiaries/national-ids/{id}")
     *
     *
     */
    public function nationalId(NationalId $nationalId): JsonResponse
    {
        return $this->json($nationalId);
    }

    /**
     * @Rest\Get("/web-app/v1/beneficiaries/phones")
     *
     *
     */
    public function phones(PhoneFilterInputType $filter, PhoneRepository $phoneRepository): JsonResponse
    {
        $params = $phoneRepository->findByParams($filter);

        return $this->json($params);
    }

    /**
     * @Rest\Get("/web-app/v1/beneficiaries/phones/{id}")
     *
     *
     */
    public function phone(Phone $phone): JsonResponse
    {
        return $this->json($phone);
    }

    /**
     * @Rest\Get("/web-app/v1/beneficiaries/{id}")
     *
     *
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
     *
     */
    public function update(Beneficiary $beneficiary, BenefciaryPatchInputType $inputType): JsonResponse
    {
        if ($beneficiary->getArchived()) {
            throw $this->createNotFoundException();
        }

        $this->beneficiaryService->patch($beneficiary, $inputType);

        return $this->json($beneficiary);
    }

    /**
     * @Rest\Get("/web-app/v1/beneficiaries")
     *
     *
     */
    public function beneficiaries(
        BeneficiaryFilterInputType $filter,
        BeneficiaryRepository $beneficiaryRepository
    ): JsonResponse {
        $beneficiaries = $beneficiaryRepository->findByParams($filter);

        return $this->json($beneficiaries);
    }

    /**
     * @Rest\Get("/web-app/v1/projects/{id}/targets/{target}/beneficiaries")
     *
     *
     */
    public function getBeneficiaries(
        Project $project,
        string $target,
        BeneficiarySelectedFilterInputType $filter,
        BeneficiaryRepository $beneficiaryRepository,
        AssistanceRepository $assistanceRepository
    ): JsonResponse {
        if (!in_array($target, AssistanceTargetType::values())) {
            throw $this->createNotFoundException(
                'Invalid target. Allowed are ' . implode(', ', AssistanceTargetType::values())
            );
        }

        if ($filter->hasExcludeAssistance()) {
            $assistanceId = $filter->getExcludeAssistance();
            /** @var Assistance $excludedAssistance */
            $excludedAssistance = $assistanceRepository->find($assistanceId);
            $beneficiaries = $beneficiaryRepository->getNotSelectedBeneficiariesOfProject(
                $project,
                $target,
                $excludedAssistance
            );
        } else {
            $beneficiaries = $beneficiaryRepository->getAllOfProject($project, $target);
        }

        return $this->json(new Paginator($beneficiaries));
    }
}
