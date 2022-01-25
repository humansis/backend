<?php

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Household;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\AddHouseholdsToProjectInputType;
use NewApiBundle\InputType\HouseholdCreateInputType;
use NewApiBundle\InputType\HouseholdFilterInputType;
use NewApiBundle\InputType\HouseholdOrderInputType;
use NewApiBundle\InputType\HouseholdUpdateInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;

class HouseholdController extends AbstractController
{
    /**
     * @Rest\Get("/web-app/v1/households/exports")
     *
     * @param Request                  $request
     * @param HouseholdFilterInputType $filter
     * @param Pagination               $pagination
     * @param HouseholdOrderInputType  $order
     *
     * @return Response
     */
    public function exports(Request $request, HouseholdFilterInputType $filter, Pagination $pagination, HouseholdOrderInputType $order): Response
    {
        if (!$request->query->has('type')) {
            throw $this->createNotFoundException('Missing query attribute type');
        }
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        try {
            $filename = $this->get('beneficiary.beneficiary_service')->exportToCsv(
                $request->query->get('type'),
                $request->headers->get('country'),
                $filter, $pagination, $order
            );
        } catch (BadRequestHttpException $e) {
            return new JsonResponse([
                'code' => 400,
                'errors' => [[
                    'message' => $e->getMessage(),
                ]],
            ],Response::HTTP_BAD_REQUEST);
        }

        $response = new BinaryFileResponse(getcwd().'/'.$filename);
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
     * @Rest\Post("/web-app/v1/households")
     *
     * @param HouseholdCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(HouseholdCreateInputType $inputType): JsonResponse
    {
        $object = $this->get('beneficiary.household_service')->create($inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Put("/web-app/v1/households/{id}")
     *
     * @param Household                $household
     * @param HouseholdUpdateInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(Household $household, HouseholdUpdateInputType $inputType): JsonResponse
    {
        $object = $this->get('beneficiary.household_service')->update($household, $inputType);

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
        $this->get('beneficiary.household_service')->remove($household);

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
