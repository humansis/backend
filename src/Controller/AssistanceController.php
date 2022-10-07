<?php

declare(strict_types=1);

namespace Controller;

use Entity\User;
use Exception\ExportNoDataException;
use InputType\Assistance\UpdateAssistanceInputType;
use InvalidArgumentException;
use Pagination\Paginator;
use Entity\Assistance;
use Enum\AssistanceType;
use PhpOffice\PhpSpreadsheet\Exception;
use Repository\AssistanceRepository;
use Utils\AssistanceService;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
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
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;
use Component\Assistance\Domain\Assistance as DomainAssistance;

class AssistanceController extends AbstractController
{
    /** @var VulnerabilityScoreExport */
    private $vulnerabilityScoreExport;

    /** @var AssistanceService */
    private $assistanceService;

    /** @var AssistanceBankReportExport */
    private $assistanceBankReportExport;

    public function __construct(
        VulnerabilityScoreExport $vulnerabilityScoreExport,
        AssistanceService $assistanceService,
        AssistanceBankReportExport $assistanceBankReportExport
    ) {
        $this->vulnerabilityScoreExport = $vulnerabilityScoreExport;
        $this->assistanceService = $assistanceService;
        $this->assistanceBankReportExport = $assistanceBankReportExport;
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/statistics")
     *
     * @param Request $request
     * @param AssistanceStatisticsFilterInputType $filter
     * @param AssistanceQuery $assistanceQuery
     * @param AssistanceRepository $assistanceRepository
     * @param AssistanceFactory $assistanceFactory
     *
     * @return JsonResponse
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function statistics(
        Request $request,
        AssistanceStatisticsFilterInputType $filter,
        AssistanceQuery $assistanceQuery,
        AssistanceRepository $assistanceRepository,
        AssistanceFactory $assistanceFactory
    ): JsonResponse {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $statistics = [];
        if ($filter->hasIds()) {
            foreach ($filter->getIds() as $key => $id) {
                $statistics[] = $assistanceQuery->find($id)->getStatistics($countryIso3);
            }
        } else {
            $assistanceInCountry = $assistanceRepository->findByCountryIso3($countryIso3);
            foreach ($assistanceInCountry as $assistance) {
                $assistanceDomain = $assistanceFactory->hydrate($assistance);
                $statistics[] = $assistanceDomain->getStatistics($countryIso3);
            }
        }

        return $this->json(new Paginator($statistics));
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/statistics")
     * @ParamConverter("assistance", options={"mapping": {"id": "id"}})
     *
     * @param Assistance $assistance
     * @param AssistanceFactory $factory
     *
     * @return JsonResponse
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function assistanceStatistics(Assistance $assistance, AssistanceFactory $factory): JsonResponse
    {
        $statistics = $factory->hydrate($assistance)->getStatistics();

        return $this->json($statistics);
    }

    /**
     * @Rest\Get("/web-app/v1/assistances")
     *
     * @param Request $request
     * @param AssistanceFilterInputType $filter
     * @param Pagination $pagination
     * @param AssistanceOrderInputType $orderBy
     *
     * @return JsonResponse
     */
    public function assistances(
        Request $request,
        AssistanceFilterInputType $filter,
        Pagination $pagination,
        AssistanceOrderInputType $orderBy
    ): JsonResponse {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $assistances = $this->getDoctrine()->getRepository(Assistance::class)->findByParams(
            $countryIso3,
            $filter,
            $orderBy,
            $pagination
        );

        return $this->json($assistances);
    }

    /**
     * @Rest\Post("/web-app/v1/assistances")
     *
     * @param AssistanceCreateInputType $inputType
     * @param AssistanceFactory $factory
     * @param AssistanceRepository $repository
     *
     * @return JsonResponse
     * @throws CsvParserException
     * @throws EntityNotFoundException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    public function create(
        AssistanceCreateInputType $inputType,
        AssistanceFactory $factory,
        AssistanceRepository $repository
    ): JsonResponse {
        $assistance = $factory->create($inputType);
        $repository->save($assistance);

        return $this->json($assistance->getAssistanceRoot());
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}")
     *
     * @param Assistance $assistance
     *
     * @return JsonResponse
     */
    public function item(Assistance $assistance): JsonResponse
    {
        if ($assistance->getArchived()) {
            $this->createNotFoundException();
        }

        return $this->json($assistance);
    }

    /**
     * @Rest\Patch("/web-app/v1/assistances/{id}")
     *
     * @param Assistance $assistanceRoot
     * @param UpdateAssistanceInputType $updateAssistanceInputType
     *
     * @return JsonResponse
     */
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
     * @Rest\Delete("/web-app/v1/assistances/{id}")
     *
     * @param Assistance $assistance
     *
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    public function delete(Assistance $assistance): JsonResponse
    {
        $this->assistanceService->delete($assistance);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/bank-report/exports")
     *
     * @param Assistance $assistance
     * @param Request $request
     *
     * @return Response
     */
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

    /**
     * @Rest\Get("/web-app/v1/projects/{id}/assistances/exports")
     *
     * @param Project $project
     * @param Request $request
     *
     * @return Response
     */
    public function exports(Project $project, Request $request): Response
    {
        $request->query->add(['officialDistributions' => $project->getId()]);

        return $this->forward(ExportController::class . '::exportAction', [], $request->query->all());
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/vulnerability-scores/exports")
     *
     * @param Assistance $assistance
     * @param Request $request
     *
     * @return Response
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws Exception
     */
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
     * @Rest\Post("/web-app/v1/assistances/vulnerability-scores/exports")
     *
     * @throws Exception
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @throws EntityNotFoundException
     */
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
     * @param DomainAssistance $assistance
     * @param string $type
     * @param int|null $threshold
     *
     * @return Response
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws Exception
     */
    private function scoresFromAssistance(DomainAssistance $assistance, string $type, int $threshold = null): Response
    {
        try {
            $filename = $this->vulnerabilityScoreExport->export($assistance, $type, $threshold);
        } catch (ExportNoDataException $e) {
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

    /**
     * @Rest\Get("/web-app/v1/projects/{id}/assistances")
     *
     * @param Project $project
     * @param Pagination $pagination
     * @param ProjectsAssistanceFilterInputType $filter
     * @param AssistanceOrderInputType $orderBy
     *
     * @return JsonResponse
     */
    public function getProjectAssistances(
        Project $project,
        Pagination $pagination,
        ProjectsAssistanceFilterInputType $filter,
        AssistanceOrderInputType $orderBy
    ): JsonResponse {
        /** @var AssistanceRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Assistance::class);

        $assistances = $repository->findByProject($project, null, $filter, $orderBy, $pagination);

        return $this->json($assistances);
    }
}
