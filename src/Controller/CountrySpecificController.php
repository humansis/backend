<?php

declare(strict_types=1);

namespace Controller;

use Entity\CountrySpecific;
use Entity\CountrySpecificAnswer;
use Controller\ExportController;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\CountrySpecificCreateInputType;
use InputType\CountrySpecificFilterInputType;
use InputType\CountrySpecificOrderInputType;
use InputType\CountrySpecificUpdateInputType;
use Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Utils\CountrySpecificService;

class CountrySpecificController extends AbstractController
{
    public function __construct(private readonly CountrySpecificService $countrySpecificService)
    {
    }

    /**
     * @Rest\Get("/web-app/v1/country-specifics/exports")
     *
     *
     */
    public function exports(Request $request): JsonResponse
    {
        $request->query->add([
            'countries' => true,
            '__country' => $request->headers->get('country'),
        ]);

        return $this->forward(ExportController::class . '::exportAction', [], $request->query->all());
    }

    /**
     * @Rest\Get("/web-app/v1/country-specifics/answers/{id}")
     *
     *
     */
    public function answer(CountrySpecificAnswer $object): JsonResponse
    {
        return $this->json($object);
    }

    /**
     * @Rest\Get("/web-app/v1/country-specifics/{id}")
     *
     *
     */
    public function item(CountrySpecific $object): JsonResponse
    {
        return $this->json($object);
    }

    /**
     * @Rest\Get("/web-app/v1/country-specifics")
     *
     *
     */
    public function list(
        Request $request,
        CountrySpecificFilterInputType $filter,
        Pagination $pagination,
        CountrySpecificOrderInputType $orderBy
    ): JsonResponse {
        if (!$request->headers->has('country')) {
            throw new BadRequestHttpException('Missing country header');
        }

        $countrySpecifics = $this->getDoctrine()->getRepository(CountrySpecific::class)
            ->findByParams($request->headers->get('country'), $filter, $orderBy, $pagination);

        return $this->json($countrySpecifics);
    }

    /**
     * @Rest\Post("/web-app/v1/country-specifics")
     *
     *
     * @throws Exception
     */
    public function create(CountrySpecificCreateInputType $inputType): JsonResponse
    {
        $countrySpecific = new CountrySpecific($inputType->getField(), $inputType->getType(), $inputType->getIso3());

        try {
            $this->getDoctrine()->getManager()->persist($countrySpecific);
            $this->getDoctrine()->getManager()->flush();
        } catch (UniqueConstraintViolationException) {
            return new JsonResponse(
                "Country specific option with the same name already exists, please choose another name.",
                \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST
            );
        }

        return $this->json($countrySpecific);
    }

    /**
     * @Rest\Put("/web-app/v1/country-specifics/{id}")
     *
     *
     * @throws Exception
     */
    public function update(CountrySpecific $countrySpecific, CountrySpecificUpdateInputType $inputType): JsonResponse
    {
        $countrySpecific->setFieldString($inputType->getField());
        $countrySpecific->setType($inputType->getType());

        try {
            $this->getDoctrine()->getManager()->persist($countrySpecific);
            $this->getDoctrine()->getManager()->flush();
        } catch (UniqueConstraintViolationException) {
            return new JsonResponse(
                "Country specific option with the same name already exists, please choose another name.",
                \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST
            );
        }

        return $this->json($countrySpecific);
    }

    /**
     * @Rest\Delete("/web-app/v1/country-specifics/{id}")
     *
     *
     */
    public function delete(CountrySpecific $object): JsonResponse
    {
        $this->countrySpecificService->delete($object);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
