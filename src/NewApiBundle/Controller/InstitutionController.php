<?php

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Institution;
use BeneficiaryBundle\Repository\InstitutionRepository;
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
     * @param InstitutionFilterInputType $filter
     * @param Pagination                 $pagination
     * @param InstitutionOrderInputType  $orderBy
     *
     * @return JsonResponse
     */
    public function list(Request $request, InstitutionFilterInputType $filter, Pagination $pagination, InstitutionOrderInputType $orderBy): JsonResponse
    {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        /** @var InstitutionRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Institution::class);
        $data = $repository->findByParams($request->headers->get('country'), $filter, $orderBy, $pagination);

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
        $institution = $this->get('beneficiary.institution_service')->create($inputType);

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
        $institution = $this->get('beneficiary.institution_service')->update($institution, $inputType);

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
        $this->get('beneficiary.institution_service')->remove($institution);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
