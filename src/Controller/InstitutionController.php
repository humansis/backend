<?php

namespace Controller;

use Doctrine\Persistence\ManagerRegistry;
use Entity\Institution;
use Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\AssistanceInstitutionsFilterInputType;
use InputType\InstitutionCreateInputType;
use InputType\InstitutionFilterInputType;
use InputType\InstitutionOrderInputType;
use InputType\InstitutionUpdateInputType;
use Request\Pagination;
use Entity\Project;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Utils\InstitutionService;

class InstitutionController extends AbstractController
{
    public function __construct(private readonly InstitutionService $institutionService, private readonly ManagerRegistry $managerRegistry)
    {
    }

    /**
     * @Rest\Get("/web-app/v1/institutions/{id}")
     *
     *
     */
    public function item(Institution $institution): JsonResponse
    {
        if (true === $institution->getArchived()) {
            throw $this->createNotFoundException();
        }

        return $this->json($institution);
    }

    /**
     * @Rest\Get("/web-app/v1/institutions")
     *
     *
     */
    public function list(
        Request $request,
        Pagination $pagination,
        InstitutionFilterInputType $filter,
        InstitutionOrderInputType $orderBy
    ): JsonResponse {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        $data = $this->managerRegistry->getRepository(Institution::class)
            ->findByParams($request->headers->get('country'), $filter, $orderBy, $pagination);

        return $this->json($data);
    }

    /**
     * @Rest\Post("/web-app/v1/institutions")
     *
     *
     */
    public function create(InstitutionCreateInputType $inputType): JsonResponse
    {
        $institution = $this->institutionService->create($inputType);

        return $this->json($institution);
    }

    /**
     * @Rest\Put("/web-app/v1/institutions/{id}")
     *
     *
     */
    public function update(Institution $institution, InstitutionUpdateInputType $inputType): JsonResponse
    {
        $institution = $this->institutionService->update($institution, $inputType);

        return $this->json($institution);
    }

    /**
     * @Rest\Delete("/web-app/v1/institutions/{id}")
     *
     *
     * @return JsonResponse
     */
    public function delete(Institution $institution)
    {
        $this->institutionService->remove($institution);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Get("/web-app/v1/projects/{id}/institutions")
     *
     *
     */
    public function institutionsByProject(Project $project): JsonResponse
    {
        $institutions = $this->managerRegistry->getRepository(Institution::class)->findByProject($project);

        return $this->json($institutions);
    }
}
