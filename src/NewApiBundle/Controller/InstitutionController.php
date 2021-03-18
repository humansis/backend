<?php

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Institution;
use BeneficiaryBundle\Repository\InstitutionRepository;
use BeneficiaryBundle\Utils\InstitutionService;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\InstitutionCreateInputType;
use NewApiBundle\InputType\InstitutionFilterInputType;
use NewApiBundle\InputType\InstitutionOrderInputType;
use NewApiBundle\InputType\InstitutionUpdateInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InstitutionController extends AbstractController
{
    /** @var InstitutionService */
    private $institutionService;

    /**
     * InstitutionController constructor.
     *
     * @param InstitutionService $institutionService
     */
    public function __construct(InstitutionService $institutionService)
    {
        $this->institutionService = $institutionService;
    }

    /**
     * @Rest\Get("/institutions/{id}")
     *
     * @param Institution $institution
     *
     * @return JsonResponse
     */
    public function item(Institution $institution): JsonResponse
    {
        if (true === $institution->getArchived()) {
            throw $this->createNotFoundException();
        }

        return $this->json($institution);
    }

    /**
     * @Rest\Get("/institutions")
     *
     * @param Request                    $request
     * @param Pagination                 $pagination
     * @param InstitutionFilterInputType $filter
     * @param InstitutionOrderInputType  $orderBy
     *
     * @return JsonResponse
     */
    public function list(Request $request, Pagination $pagination, InstitutionFilterInputType $filter, InstitutionOrderInputType $orderBy): JsonResponse
    {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        $data = $this->getDoctrine()->getRepository(Institution::class)
            ->findByParams($request->headers->get('country'), $filter, $orderBy, $pagination);

        return $this->json($data);
    }

    /**
     * @Rest\Post("/institutions")
     *
     * @param InstitutionCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(InstitutionCreateInputType $inputType): JsonResponse
    {
        $institution = $this->institutionService->create($inputType);

        return $this->json($institution);
    }

    /**
     * @Rest\Put("/institutions/{id}")
     *
     * @param Institution                $institution
     * @param InstitutionUpdateInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(Institution $institution, InstitutionUpdateInputType $inputType): JsonResponse
    {
        $institution = $this->institutionService->update($institution, $inputType);

        return $this->json($institution);
    }

    /**
     * @Rest\Delete("/institutions/{id}")
     *
     * @param Institution $institution
     *
     * @return JsonResponse
     */
    public function delete(Institution $institution)
    {
        $this->institutionService->remove($institution);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
