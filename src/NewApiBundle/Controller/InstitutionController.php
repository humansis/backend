<?php

namespace NewApiBundle\Controller;

use NewApiBundle\Entity\Institution;
use NewApiBundle\Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\AssistanceInstitutionsFilterInputType;
use NewApiBundle\InputType\InstitutionCreateInputType;
use NewApiBundle\InputType\InstitutionFilterInputType;
use NewApiBundle\InputType\InstitutionOrderInputType;
use NewApiBundle\InputType\InstitutionUpdateInputType;
use NewApiBundle\Request\Pagination;
use NewApiBundle\Entity\Project;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InstitutionController extends AbstractController
{
    /**
     * @Rest\Get("/web-app/v1/institutions/{id}")
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
     * @Rest\Get("/web-app/v1/institutions")
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
     * @Rest\Post("/web-app/v1/institutions")
     *
     * @param InstitutionCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(InstitutionCreateInputType $inputType): JsonResponse
    {
        $institution = $this->get('beneficiary.institution_service')->create($inputType);

        return $this->json($institution);
    }

    /**
     * @Rest\Put("/web-app/v1/institutions/{id}")
     *
     * @param Institution                $institution
     * @param InstitutionUpdateInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(Institution $institution, InstitutionUpdateInputType $inputType): JsonResponse
    {
        $institution = $this->get('beneficiary.institution_service')->update($institution, $inputType);

        return $this->json($institution);
    }

    /**
     * @Rest\Delete("/web-app/v1/institutions/{id}")
     *
     * @param Institution $institution
     *
     * @return JsonResponse
     */
    public function delete(Institution $institution)
    {
        $this->get('beneficiary.institution_service')->remove($institution);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Get("/web-app/v1/projects/{id}/institutions")
     *
     * @param Project $project
     *
     * @return JsonResponse
     */
    public function institutionsByProject(Project $project): JsonResponse
    {
        $institutions = $this->getDoctrine()->getRepository(Institution::class)->findByProject($project);

        return $this->json($institutions);
    }
}
