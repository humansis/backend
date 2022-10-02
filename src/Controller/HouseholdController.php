<?php

namespace Controller;

use Entity\Household;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\AddHouseholdsToProjectInputType;
use InputType\HouseholdCreateInputType;
use InputType\HouseholdFilterInputType;
use InputType\HouseholdOrderInputType;
use InputType\HouseholdUpdateInputType;
use Repository\HouseholdRepository;
use Request\Pagination;
use Entity\Project;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Utils\HouseholdService;
use Utils\BeneficiaryService;
use Utils\ExportTableServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Utils\TransformDataService;

class HouseholdController extends AbstractController
{

    /** @var HouseholdService */
    private $householdService;

    /** @var BeneficiaryService */
    private $beneficiaryService;

    /** @var HouseholdRepository */
    private $householdRepository;

    /** @var ExportTableServiceInterface */
    private $exportTableService;

    /** @var TransformDataService */
    private $transformDataService;

    /**
     * @param HouseholdService            $householdService
     * @param HouseholdRepository         $householdRepository
     * @param BeneficiaryService          $beneficiaryService
     * @param ExportTableServiceInterface $exportTableService
     */
    public function __construct(HouseholdService $householdService, HouseholdRepository $householdRepository, BeneficiaryService $beneficiaryService, ExportTableServiceInterface $exportTableService, TransformDataService $transformDataService)
    {
        $this->householdService = $householdService;
        $this->householdRepository = $householdRepository;
        $this->beneficiaryService = $beneficiaryService;
        $this->exportTableService = $exportTableService;
        $this->transformDataService = $transformDataService;
    }

    /**
     * @Rest\Get("/web-app/v1/households/exports")
     *
     * @param Request                  $request
     * @param HouseholdFilterInputType $filter
     * @param Pagination               $pagination
     * @param HouseholdOrderInputType  $order
     *
     * @return StreamedResponse
     */
    public function exports(Request $request, HouseholdFilterInputType $filter, Pagination $pagination, HouseholdOrderInputType $order): Response
    {
        if (!$request->query->has('type')) {
            throw $this->createNotFoundException('Missing query attribute type');
        }
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }
        $beneficiaries = $this->beneficiaryService->findBeneficiarys(
            $request->headers->get('country'),
            $filter, $pagination, $order);
        $exportableTable = $this->transformDataService->transform($beneficiaries);
        return $this->exportTableService->export($exportableTable,'beneficiaryhousehoulds',$request->query->get('type'));
    }

    /**
     * @Rest\Get("/web-app/v1/households/{id}")
     *
     * @param Household $household
     *
     * @return JsonResponse
     */
    public function item(Household $household): JsonResponse
    {
        if (true === $household->getArchived()) {
            throw $this->createNotFoundException();
        }

        return $this->json($household);
    }

    /**
     * @Rest\Get("/web-app/v1/households")
     *
     * @param Request                  $request
     * @param HouseholdFilterInputType $filter
     * @param Pagination               $pagination
     * @param HouseholdOrderInputType  $orderBy
     *
     * @return JsonResponse
     */
    public function list(Request $request, HouseholdFilterInputType $filter, Pagination $pagination, HouseholdOrderInputType $orderBy): JsonResponse
    {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        $data = $this->householdRepository->findByParams($request->headers->get('country'), $filter, $orderBy, $pagination);

        return $this->json($data);
    }

    /**
     * @Rest\Post("/web-app/v1/households")
     *
     * @param Request                  $request
     * @param HouseholdCreateInputType $inputType
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function create(Request $request, HouseholdCreateInputType $inputType): JsonResponse
    {
        $household = $this->householdService->create($inputType, $this->getCountryCode($request));
        $this->getDoctrine()->getManager()->flush();
        return $this->json($household);
    }

    /**
     * @Rest\Put("/web-app/v1/households/{id}")
     *
     * @param Request                  $request
     * @param Household                $household
     * @param HouseholdUpdateInputType $inputType
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function update(Request $request, Household $household, HouseholdUpdateInputType $inputType): JsonResponse
    {
        $object = $this->householdService->update($household, $inputType, $this->getCountryCode($request));
        $this->getDoctrine()->getManager()->flush();
        return $this->json($object);
    }

    /**
     * @Rest\Delete("/web-app/v1/households/{id}")
     *
     * @param Household $household
     *
     * @return JsonResponse
     */
    public function delete(Household $household): JsonResponse
    {
        $this->householdService->remove($household);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Put("/web-app/v1/projects/{id}/households")
     *
     * @param Project                         $project
     *
     * @param AddHouseholdsToProjectInputType $inputType
     *
     * @return JsonResponse
     */
    public function addHouseholdsToProject(Project $project, AddHouseholdsToProjectInputType $inputType): JsonResponse
    {
        $this->get('project.project_service')->addHouseholds($project, $inputType);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
