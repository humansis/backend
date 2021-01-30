<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\DonorCreateInputType;
use NewApiBundle\InputType\DonorOrderInputType;
use NewApiBundle\InputType\DonorUpdateInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Donor;
use ProjectBundle\Utils\DonorService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DonorController extends AbstractController
{
    /** @var DonorService */
    private $donorService;

    /**
     * DonorController constructor.
     *
     * @param DonorService $donorService
     */
    public function __construct(DonorService $donorService)
    {
        $this->donorService = $donorService;
    }

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
     * @param Pagination          $pagination
     * @param DonorOrderInputType $orderBy
     *
     * @return JsonResponse
     */
    public function list(Pagination $pagination, DonorOrderInputType $orderBy): JsonResponse
    {
        $countrySpecifics = $this->getDoctrine()->getRepository(Donor::class)
            ->findByParams($orderBy, $pagination);

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
        $donor = $this->donorService->create($inputType);

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
        $this->donorService->update($donor, $inputType);

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
        $this->donorService->delete($object);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
