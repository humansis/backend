<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use CommonBundle\Controller\ExportController;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\CountrySpecificCreateInputType;
use NewApiBundle\InputType\CountrySpecificFilterInputType;
use NewApiBundle\InputType\CountrySpecificOrderInputType;
use NewApiBundle\InputType\CountrySpecificUpdateInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CountrySpecificController extends AbstractController
{
    /**
     * @Rest\Get("/web-app/v1/country-specifics/exports")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function exports(Request $request): JsonResponse
    {
        $request->query->add([
            'countries' => true,
            '__country' => $request->headers->get('country'),
        ]);

        return $this->forward(ExportController::class.'::exportAction', [], $request->query->all());
    }

    /**
     * @Rest\Get("/web-app/v1/country-specifics/answers/{id}")
     *
     * @param CountrySpecificAnswer $object
     *
     * @return JsonResponse
     */
    public function answer(CountrySpecificAnswer $object): JsonResponse
    {
        return $this->json($object);
    }

    /**
     * @Rest\Get("/web-app/v1/country-specifics/{id}")
     *
     * @param CountrySpecific $object
     *
     * @return JsonResponse
     */
    public function item(CountrySpecific $object): JsonResponse
    {
        return $this->json($object);
    }

    /**
     * @Rest\Get("/web-app/v1/country-specifics")
     *
     * @param Request                        $request
     * @param CountrySpecificFilterInputType $filter
     * @param Pagination                     $pagination
     * @param CountrySpecificOrderInputType  $orderBy
     *
     * @return JsonResponse
     */
    public function list(
        Request $request,
        CountrySpecificFilterInputType $filter,
        Pagination $pagination,
        CountrySpecificOrderInputType $orderBy
    ): JsonResponse
    {
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
     * @param CountrySpecificCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(CountrySpecificCreateInputType $inputType): JsonResponse
    {
        $countrySpecific = new CountrySpecific($inputType->getField(), $inputType->getType(), $inputType->getIso3());

        $this->getDoctrine()->getManager()->persist($countrySpecific);
        $this->getDoctrine()->getManager()->flush();

        return $this->json($countrySpecific);
    }

    /**
     * @Rest\Put("/web-app/v1/country-specifics/{id}")
     *
     * @param CountrySpecific                $countrySpecific
     * @param CountrySpecificUpdateInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(CountrySpecific $countrySpecific, CountrySpecificUpdateInputType $inputType): JsonResponse
    {
        $countrySpecific->setFieldString($inputType->getField());
        $countrySpecific->setType($inputType->getType());

        $this->getDoctrine()->getManager()->persist($countrySpecific);
        $this->getDoctrine()->getManager()->flush();

        return $this->json($countrySpecific);
    }

    /**
     * @Rest\Delete("/web-app/v1/country-specifics/{id}")
     *
     * @param CountrySpecific $object
     *
     * @return JsonResponse
     */
    public function delete(CountrySpecific $object): JsonResponse
    {
        $this->get('beneficiary.country_specific_service')->delete($object);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
