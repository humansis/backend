<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\DonorCreateInputType;
use NewApiBundle\InputType\DonorFilterInputType;
use NewApiBundle\InputType\DonorOrderInputType;
use NewApiBundle\InputType\DonorUpdateInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Donor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DonorController extends AbstractController
{
    /**
     * @Rest\Get("/donors/{id}")
     *
     * @param Donor $object
     *
     * @return JsonResponse
     */
    public function item(Donor $object): JsonResponse
    {
        return $this->json($object);
    }

    /**
     * @Rest\Get("/donors")
     *
     * @param Pagination           $pagination
     * @param DonorOrderInputType  $orderBy
     * @param DonorFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function list(Pagination $pagination, DonorOrderInputType $orderBy, DonorFilterInputType $filter): JsonResponse
    {
        $countrySpecifics = $this->getDoctrine()->getRepository(Donor::class)
            ->findByParams($orderBy, $pagination, $filter);

        return $this->json($countrySpecifics);
    }

    /**
     * @Rest\Post("/donors")
     *
     * @param DonorCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(DonorCreateInputType $inputType): JsonResponse
    {
        $donor = $this->get('project.donor_service')->create($inputType);

        return $this->json($donor);
    }

    /**
     * @Rest\Put("/donors/{id}")
     *
     * @param Donor                $donor
     * @param DonorUpdateInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(Donor $donor, DonorUpdateInputType $inputType): JsonResponse
    {
        $this->get('project.donor_service')->update($donor, $inputType);

        return $this->json($donor);
    }

    /**
     * @Rest\Delete("/donors/{id}")
     *
     * @param Donor $object
     *
     * @return JsonResponse
     */
    public function delete(Donor $object): JsonResponse
    {
        $this->get('project.donor_service')->delete($object);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
