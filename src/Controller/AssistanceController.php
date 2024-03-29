<?php

declare(strict_types=1);

namespace Controller;

use Doctrine\ORM\Exception\ORMException;
use Entity\User;
use Exception\ExportNoDataException;
use InputType\Assistance\UpdateAssistanceInputType;
use InvalidArgumentException;
use Pagination\Paginator;
use Entity\Assistance;
use Enum\AssistanceType;
use PhpOffice\PhpSpreadsheet\Exception;
use Repository\AssistanceRepository;
use Repository\ProjectRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Utils\AssistanceService;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\Assistance\AssistanceFactory;
use Component\Assistance\AssistanceQuery;
use Enum\ModalityType;
use Exception\CsvParserException;
use Export\AssistanceBankReportExport;
use Export\VulnerabilityScoreExport;
use InputType\AssistanceCreateInputType;
use InputType\AssistanceFilterInputType;
use InputType\AssistanceOrderInputType;
use InputType\AssistanceStatisticsFilterInputType;
use InputType\ProjectsAssistanceFilterInputType;
use Request\Pagination;
use DBAL\SubSectorEnum;
use Entity\Project;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;
use Component\Assistance\Domain\Assistance as DomainAssistance;
use Utils\ExportTableServiceInterface;
use Utils\ProjectAssistancesTransformData;

class AssistanceController extends AbstractController
{
    public function __construct(
        private readonly VulnerabilityScoreExport $vulnerabilityScoreExport,
        private readonly AssistanceService $assistanceService,
        private readonly AssistanceBankReportExport $assistanceBankReportExport,
        private readonly ProjectAssistancesTransformData $projectAssistancesTransformData,
        private readonly ExportTableServiceInterface $exportTableService,
        private readonly AssistanceRepository $assistanceRepository,
        private readonly ProjectRepository $projectRepository
    ) {
    }

    #[Rest\Get('/web-app/v1/assistances/statistics')]
    public function statistics(
        Request $request,
        AssistanceStatisticsFilterInputType $filter,
        AssistanceQuery $assistanceQuery,
        AssistanceFactory $assistanceFactory
    ): JsonResponse {
        $countryIso3 = $request->headers->get('country');
        if (is_null($countryIso3)) {
            throw new BadRequestHttpException('Missing country header');
        }

        $statistics = [];
        if ($filter->hasIds()) {
            foreach ($filter->getIds() as $key => $id) {
                $statistics[] = $assistanceQuery->find($id)->getStatistics($countryIso3);
            }
        } else {
            $assistanceInCountry = $this->assistanceRepository->findByCountryIso3($countryIso3);
            foreach ($assistanceInCountry as $assistance) {
                $assistanceDomain = $assistanceFactory->hydrate($assistance);
                $statistics[] = $assistanceDomain->getStatistics($countryIso3);
            }
        }

        return $this->json(new Paginator($statistics));
    }

    #[Rest\Get('/web-app/v1/assistances/{id}/statistics')]
    #[ParamConverter('assistance', options: ['mapping' => ['id' => 'id']])]
    public function assistanceStatistics(Assistance $assistance, AssistanceFactory $factory): JsonResponse
    {
        $statistics = $factory->hydrate($assistance)->getStatistics();

        return $this->json($statistics);
    }

    #[Rest\Get('/web-app/v1/assistances')]
    public function assistances(
        Request $request,
        AssistanceFilterInputType $filter,
        Pagination $pagination,
        AssistanceOrderInputType $orderBy
    ): JsonResponse {
        $countryIso3 = $request->headers->get('country');
        if (is_null($countryIso3)) {
            throw new BadRequestHttpException('Missing country header');
        }

        $assistances = $this->assistanceRepository->findByParams(
            $countryIso3,
            $filter,
            $orderBy,
            $pagination
        );

        return $this->json($assistances);
    }

    /**
     * @throws CsvParserException
     * @throws EntityNotFoundException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    #[Rest\Post('/web-app/v1/assistances')]
    public function create(
        AssistanceCreateInputType $inputType,
        AssistanceFactory $factory,
        AssistanceRepository $repository
    ): JsonResponse {
        $assistance = $factory->create($inputType);
        $repository->save($assistance);

        return $this->json($assistance->getAssistanceRoot());
    }

    #[Rest\Get('/web-app/v1/assistances/{id}')]
    public function item(Assistance $assistance): JsonResponse
    {
        if ($assistance->getArchived()) {
            $this->createNotFoundException();
        }

        return $this->json($assistance);
    }

    #[Rest\Patch('/web-app/v1/assistances/{id}')]
    public function update(
        Assistance $assistanceRoot,
        UpdateAssistanceInputType $updateAssistanceInputType
    ): JsonResponse {
        /**
         * @var User $user
         */
        $user = $this->getUser();
        $assistance = $this->assistanceService->update($assistanceRoot, $updateAssistanceInputType, $user);

        return $this->json($assistance);
    }

    /**
     *
     * @throws InvalidArgumentException
     */
    #[Rest\Delete('/web-app/v1/assistances/{id}')]
    public function delete(Assistance $assistance): JsonResponse
    {
        $this->assistanceService->delete($assistance);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Rest\Get('/web-app/v1/assistances/{id}/bank-report/exports')]
    public function bankReportExports(Assistance $assistance, Request $request): Response
    {
        $type = $request->query->get('type', 'csv');
        if (!$assistance->isValidated()) {
            throw new BadRequestHttpException('Cannot download bank report for assistance which is not validated.');
        }
        if ($assistance->getAssistanceType() !== AssistanceType::DISTRIBUTION) {
            throw new BadRequestHttpException('Bank export is allowed only for Distribution type of assistance.');
        }
        if ($assistance->getSubSector() !== SubSectorEnum::MULTI_PURPOSE_CASH_ASSISTANCE) {
            throw new BadRequestHttpException(
                'Bank export is allowed only for subsector Multi purpose cash assistance.'
            );
        }
        if (!$assistance->hasModalityTypeCommodity(ModalityType::CASH)) {
            throw new BadRequestHttpException('Bank export is allowed only for assistance with Cash commodity.');
        }
        $filename = $this->assistanceBankReportExport->export($assistance, $type);
        try {
            $response = new BinaryFileResponse($filename);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($filename));
            $response->deleteFileAfterSend(true);

            return $response;
        } catch (\Exception $exception) {
            return new JsonResponse(
                $exception->getMessage(),
                $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST
            );
        }
    }

    #[Rest\Get('/web-app/v1/projects/{id}/assistances/exports')]
    public function exports(Project $project, Request $request): Response
    {
        $projectId = $project->getId();
        $type = $request->query->get('type');

        if ($type == "pdf") {
            return $this->assistanceService->exportToPdf($projectId);
        } else {
            $project = $this->projectRepository->find($project->getId());
            if (!$project) {
                throw new NotFoundHttpException("Project #$projectId missing");
            }

            $assistances = $this->assistanceRepository->findBy(['project' => $projectId, 'archived' => 0]);
            $exportableTable = $this->projectAssistancesTransformData->transformData($project, $assistances);

            return $this->exportTableService->export($exportableTable, 'distributions', $type);
        }
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws Exception
     */
    #[Rest\Get('/web-app/v1/assistances/{id}/vulnerability-scores/exports')]
    public function vulnerabilityScoresExports(
        Assistance $assistance,
        Request $request,
        AssistanceFactory $factory
    ): Response {
        if (!$request->query->has('type')) {
            throw $this->createNotFoundException('Missing query attribute type');
        }

        $type = $request->query->get('type');

        return $this->scoresFromAssistance($factory->hydrate($assistance), $type);
    }

    /**
     *
     * @throws Exception
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @throws EntityNotFoundException
     */
    #[Rest\Post('/web-app/v1/assistances/vulnerability-scores/exports')]
    public function vulnerabilityScoresPreExport(
        AssistanceCreateInputType $inputType,
        AssistanceFactory $factory,
        Request $request
    ): Response {
        if (!$request->query->has('type')) {
            throw $this->createNotFoundException('Missing query attribute type');
        }

        $type = $request->query->get('type');
        $assistance = $factory->create($inputType);

        return $this->scoresFromAssistance($assistance, $type, $inputType->getThreshold());
    }

    /**
     * @param int|null $threshold
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws Exception
     */
    private function scoresFromAssistance(DomainAssistance $assistance, string $type, int $threshold = null): Response
    {
        try {
            $filename = $this->vulnerabilityScoreExport->export($assistance, $type, $threshold);
        } catch (ExportNoDataException) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }
        if (!$filename) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse(getcwd() . '/' . $filename);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);

        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
        if ($mimeTypeGuesser->isGuesserSupported()) {
            $response->headers->set('Content-Type', $mimeTypeGuesser->guessMimeType(getcwd() . '/' . $filename));
        }

        return $response;
    }

    #[Rest\Get('/web-app/v1/projects/{id}/assistances')]
    public function getProjectAssistances(
        Project $project,
        Pagination $pagination,
        ProjectsAssistanceFilterInputType $filter,
        AssistanceOrderInputType $orderBy
    ): JsonResponse {
        $assistances = $this->assistanceRepository->findByProject($project, null, $filter, $orderBy, $pagination);

        return $this->json($assistances);
    }
}
