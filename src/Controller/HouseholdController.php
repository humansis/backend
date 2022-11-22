<?php

namespace Controller;

use Entity\Household;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\AddHouseholdsToProjectInputType;
use InputType\HouseholdCreateInputType;
use InputType\HouseholdFilterInputType;
use InputType\HouseholdOrderInputType;
use InputType\HouseholdUpdateInputType;
use Repository\HouseholdRepository;
use Request\Pagination;
use Entity\Project;
use Utils\BeneficiaryService;
use Utils\HouseholdService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;
use Utils\ProjectService;

class HouseholdController extends AbstractController
{
    /** @var HouseholdService */
    private $householdService;

    /** @var HouseholdRepository */
    private $householdRepository;

    /** @var BeneficiaryService */
    private $beneficiaryService;

    /** @var ProjectService */
    private $projectService;

    /**
     * @param HouseholdService $householdService
     * @param HouseholdRepository $householdRepository
     * @param BeneficiaryService $beneficiaryService
     * @param ProjectService $projectService
     */
    public function __construct(
        HouseholdService $householdService,
        HouseholdRepository $householdRepository,
        BeneficiaryService $beneficiaryService,
        ProjectService $projectService
    ) {
        $this->householdService = $householdService;
        $this->householdRepository = $householdRepository;
        $this->beneficiaryService = $beneficiaryService;
        $this->projectService = $projectService;
    }

    /**
     * @Rest\Get("/web-app/v1/households/exports")
     *
     * @param Request $request
     * @param HouseholdFilterInputType $filter
     * @param Pagination $pagination
     * @param HouseholdOrderInputType $order
     *
     * @return Response
     */
    public function exports(
        Request $request,
        HouseholdFilterInputType $filter,
        Pagination $pagination,
        HouseholdOrderInputType $order
    ): Response {
        if (!$request->query->has('type')) {
            throw $this->createNotFoundException('Missing query attribute type');
        }
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        try {
            $filename = $this->beneficiaryService->exportToCsv(
                $request->query->get('type'),
                $request->headers->get('country'),
                $filter,
                $pagination,
                $order
            );
        } catch (BadRequestHttpException $e) {
            return new JsonResponse([
                'code' => 400,
                'errors' => [
                    [
                        'message' => $e->getMessage(),
                    ],
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        $response = new BinaryFileResponse(getcwd() . '/' . $filename);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, '$filename');
        $response->deleteFileAfterSend(true);

        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
        if ($mimeTypeGuesser->isGuesserSupported()) {
            $response->headers->set('Content-Type', $mimeTypeGuesser->guessMimeType($filename));
        } else {
            $response->headers->set('Content-Type', 'text/plain');
        }

        return $response;
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
     * @param Request $request
     * @param HouseholdFilterInputType $filter
     * @param Pagination $pagination
     * @param HouseholdOrderInputType $orderBy
     *
     * @return JsonResponse
     */
    public function list(
        Request $request,
        HouseholdFilterInputType $filter,
        Pagination $pagination,
        HouseholdOrderInputType $orderBy
    ): JsonResponse {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        $data = $this->householdRepository->findByParams(
            $request->headers->get('country'),
            $filter,
            $orderBy,
            $pagination
        );

        return $this->json($data);
    }

    /**
     * @Rest\Post("/web-app/v1/households")
     *
     * @param Request $request
     * @param HouseholdCreateInputType $inputType
     *
     * @return JsonResponse
     * @throws Exception
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
     * @param Request $request
     * @param Household $household
     * @param HouseholdUpdateInputType $inputType
     *
     * @return JsonResponse
     * @throws Exception
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
     * @param Project $project
     *
     * @param AddHouseholdsToProjectInputType $inputType
     *
     * @return JsonResponse
     */
    public function addHouseholdsToProject(Project $project, AddHouseholdsToProjectInputType $inputType): JsonResponse
    {
        $this->projectService->addHouseholds($project, $inputType);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
