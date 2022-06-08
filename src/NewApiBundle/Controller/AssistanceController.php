<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Controller\ExportController;
use CommonBundle\Entity\Organization;
use CommonBundle\Pagination\Paginator;
use DateTimeInterface;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Enum\AssistanceType;
use DistributionBundle\Repository\AssistanceRepository;
use DistributionBundle\Utils\AssistanceService;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Assistance\AssistanceFactory;
use NewApiBundle\Component\Assistance\AssistanceQuery;
use NewApiBundle\Entity\AssistanceStatistics;
use NewApiBundle\Enum\ModalityType;
use NewApiBundle\Exception\ConstraintViolationException;
use NewApiBundle\Export\AssistanceBankReportExport;
use NewApiBundle\Export\VulnerabilityScoreExport;
use NewApiBundle\InputType\AssistanceCreateInputType;
use NewApiBundle\InputType\AssistanceFilterInputType;
use NewApiBundle\InputType\AssistanceOrderInputType;
use NewApiBundle\InputType\AssistanceStatisticsFilterInputType;
use NewApiBundle\InputType\ProjectsAssistanceFilterInputType;
use NewApiBundle\Request\Pagination;
use NewApiBundle\Utils\DateTime\Iso8601Converter;
use ProjectBundle\DBAL\SubSectorEnum;
use ProjectBundle\Entity\Project;
use Psr\Cache\InvalidArgumentException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;

class AssistanceController extends AbstractController
{
    /** @var VulnerabilityScoreExport */
    private $vulnerabilityScoreExport;

    /** @var AssistanceService */
    private $assistanceService;

    /** @var AssistanceBankReportExport */
    private $assistanceBankReportExport;

    /** @var SerializerInterface */
    private $serializer;


    public function __construct(VulnerabilityScoreExport $vulnerabilityScoreExport, AssistanceService $assistanceService, AssistanceBankReportExport $assistanceBankReportExport, SerializerInterface $serializer)
    {
        $this->vulnerabilityScoreExport = $vulnerabilityScoreExport;
        $this->assistanceService = $assistanceService;
        $this->assistanceBankReportExport = $assistanceBankReportExport;
        $this->serializer = $serializer;
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/statistics")
     *
     * @param Request                             $request
     * @param AssistanceStatisticsFilterInputType $filter
     * @param AssistanceQuery                     $assistanceQuery
     *
     * @return JsonResponse
     */
    public function statistics(Request $request, AssistanceStatisticsFilterInputType $filter, AssistanceQuery $assistanceQuery): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $statistics = [];
        if($filter->hasIds()){
            foreach($filter->getIds() as $key => $id){
                $statistics[] = $assistanceQuery->find($id)->getStatistics($countryIso3);
            }
        } else {

            // TODO if we search only assistance IDs we can check if statistic is in cache

            $statistics = $this->getDoctrine()->getRepository(AssistanceStatistics::class)->findByParams($countryIso3, $filter);
        }


        return $this->json(new Paginator($statistics));
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/statistics")
     * @ParamConverter("assistance", options={"mapping": {"id": "id"}})
     *
     * @param Assistance        $assistance
     * @param AssistanceFactory $factory
     *
     * @return JsonResponse
     */
    public function assistanceStatistics(Assistance $assistance, AssistanceFactory $factory): JsonResponse
    {
        $statistics = $factory->hydrate($assistance)->getStatistics();

        return $this->json($statistics);
    }

    /**
     * @Rest\Get("/web-app/v1/assistances")
     *
     * @param Request                   $request
     * @param AssistanceFilterInputType $filter
     * @param Pagination                $pagination
     * @param AssistanceOrderInputType  $orderBy
     *
     * @return JsonResponse
     */
    public function assistances(
        Request $request,
        AssistanceFilterInputType $filter,
        Pagination $pagination,
        AssistanceOrderInputType $orderBy
    ): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $assistances = $this->getDoctrine()->getRepository(Assistance::class)->findByParams($countryIso3, $filter, $orderBy, $pagination);

        return $this->json($assistances);
    }

    /**
     * @Rest\Post("/web-app/v1/assistances")
     *
     * @param AssistanceCreateInputType $inputType
     * @param AssistanceFactory         $factory
     * @param AssistanceRepository      $repository
     *
     * @return JsonResponse
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    public function create(AssistanceCreateInputType $inputType, AssistanceFactory $factory, AssistanceRepository $repository): JsonResponse
    {
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
     * @param Request              $request
     * @param Assistance           $assistanceRoot
     * @param AssistanceFactory    $factory
     * @param AssistanceRepository $repository
     *
     * @return JsonResponse
     */
    public function update(Request $request, Assistance $assistanceRoot, AssistanceFactory $factory, AssistanceRepository $repository): JsonResponse
    {
        $assistance = $factory->hydrate($assistanceRoot);
        if ($request->request->has('validated')) {
            if ($request->request->get('validated', true)) {
                $assistance->validate();
            } else {
                $assistance->unvalidate();
            }
        }

        if ($request->request->get('completed', false)) {
            $assistance->complete();
        }

        //TODO think about better input validation for PATCH method
        if ($request->request->has('dateDistribution')) {
            $date = Iso8601Converter::toDateTime($request->request->get('dateDistribution'));

            if (!$date instanceof DateTimeInterface) {
                throw new ConstraintViolationException(new ConstraintViolation(
                    "{$request->request->get('dateDistribution')} is not valid date format",
                    null,
                    [],
                    [],
                    'dateDistribution',
                    $request->request->get('dateDistribution')
                ));
            }

            $this->assistanceService->updateDateDistribution($assistanceRoot, $date);
        }

        if ($request->request->has('dateExpiration')) {
            $date = Iso8601Converter::toDateTime($request->request->get('dateExpiration'));

            if (!$date instanceof DateTimeInterface) {
                throw new ConstraintViolationException(new ConstraintViolation(
                    "{$request->request->get('dateExpiration')} is not valid date format",
                    null,
                    [],
                    [],
                    'dateExpiration',
                    $request->request->get('dateExpiration')
                ));
            }

            $this->assistanceService->updateDateExpiration($assistanceRoot, $date);
        }
        $repository->save($assistance);

        return $this->json($assistance);
    }

    /**
     * @Rest\Delete("/web-app/v1/assistances/{id}")
     *
     * @param Assistance $assistance
     *
     * @return JsonResponse
     */
    public function delete(Assistance $assistance): JsonResponse
    {
        $this->get('distribution.assistance_service')->delete($assistance);

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
        if (!$assistance->getValidated()) {
            throw new BadRequestHttpException('Cannot download bank report for assistance which is not validated.');
        }
        if ($assistance->getAssistanceType() !== AssistanceType::DISTRIBUTION) {
            throw new BadRequestHttpException('Bank export is allowed only for Distribution type of assistance.');
        }
        if ($assistance->getSubSector() !== SubSectorEnum::MULTI_PURPOSE_CASH_ASSISTANCE) {
            throw new BadRequestHttpException('Bank export is allowed only for subsector Multi purpose cash assistance.');
        }
        if (!$assistance->hasModalityTypeCommodity(ModalityType::CASH)) {
            throw new BadRequestHttpException('Bank export is allowed only for assistance with Cash commodity.');
        }
        $filename = $this->assistanceBankReportExport->export($assistance, $type);
        try {
            $response = new BinaryFileResponse($filename);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($filename));
            $response->deleteFileAfterSend(true);
            return  $response;
        } catch (\Exception $exception) {
            return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
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

        return $this->forward(ExportController::class.'::exportAction', [], $request->query->all());
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/vulnerability-scores/exports")
     *
     * @param Assistance $assistance
     * @param Request    $request
     *
     * @return Response
     */
    public function vulnerabilityScoresExports(Assistance $assistance, Request $request): Response
    {
        if (!$request->query->has('type')) {
            throw $this->createNotFoundException('Missing query attribute type');
        }

        $filename = $this->vulnerabilityScoreExport->export($assistance, $request->query->get('type'));
        if (!$filename) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse(getcwd().'/'.$filename);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);

        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
        if ($mimeTypeGuesser->isGuesserSupported()) {
            $response->headers->set('Content-Type', $mimeTypeGuesser->guessMimeType(getcwd().'/'.$filename));
        }

        return $response;
    }

    /**
     * @Rest\Get("/web-app/v1/projects/{id}/assistances")
     *
     * @param Project                           $project
     * @param Pagination                        $pagination
     * @param ProjectsAssistanceFilterInputType $filter
     * @param AssistanceOrderInputType          $orderBy
     *
     * @return JsonResponse
     */
    public function getProjectAssistances(
        Project $project,
        Pagination $pagination,
        ProjectsAssistanceFilterInputType $filter,
        AssistanceOrderInputType $orderBy
    ): JsonResponse
    {
        /** @var AssistanceRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Assistance::class);

        $assistances = $repository->findByProject($project, null, $filter, $orderBy, $pagination);

        return $this->json($assistances);
    }
}
