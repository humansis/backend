<?php

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Utils\HouseholdService;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\HouseholdCreateInputType;
use NewApiBundle\InputType\HouseholdFilterInputType;
use NewApiBundle\InputType\HouseholdOrderInputType;
use NewApiBundle\InputType\HouseholdUpdateInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HouseholdController extends AbstractController
{
    /** @var HouseholdService */
    private $householdService;

    /**
     * HouseholdController constructor.
     *
     * @param HouseholdService $householdService
     */
    public function __construct(HouseholdService $householdService)
    {
        $this->householdService = $householdService;
    }

    /**
     * @Rest\Get("/households/{id}")
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
     * @Rest\Get("/households")
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

        $data = $this->getDoctrine()->getRepository(Household::class)
            ->findByParams($request->headers->get('country'), $filter, $orderBy, $pagination);

        return $this->json($data);
    }

    /**
     * @Rest\Post("/households")
     *
     * @param HouseholdCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(HouseholdCreateInputType $inputType): JsonResponse
    {
        $object = $this->householdService->create($inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Put("/households/{id}")
     *
     * @param Household                $household
     * @param HouseholdUpdateInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(Household $household, HouseholdUpdateInputType $inputType): JsonResponse
    {
        $object = $this->householdService->update($household, $inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Delete("/households/{id}")
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
}
