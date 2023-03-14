<?php

declare(strict_types=1);

namespace Controller;

use Component\CSO\Services\CountrySpecificService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Entity\CountrySpecific;
use Entity\CountrySpecificAnswer;
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

class CountrySpecificController extends AbstractController
{
    public function __construct(
        private readonly CountrySpecificService $countrySpecificService,
        private readonly ManagerRegistry $managerRegistry
    ) {
    }

    #[Rest\Get('/web-app/v1/country-specifics/exports')]
    public function exports(Request $request): JsonResponse
    {
        $request->query->add([
            'countries' => true,
            '__country' => $request->headers->get('country'),
        ]);

        return $this->forward(ExportController::class . '::exportAction', [], $request->query->all());
    }

    #[Rest\Get('/web-app/v1/country-specifics/answers/{id}')]
    public function answer(CountrySpecificAnswer $object): JsonResponse
    {
        return $this->json($object);
    }

    #[Rest\Get('/web-app/v1/country-specifics/{id}')]
    public function item(CountrySpecific $object): JsonResponse
    {
        return $this->json($object);
    }

    #[Rest\Get('/web-app/v1/country-specifics')]
    public function list(
        Request $request,
        CountrySpecificFilterInputType $filter,
        Pagination $pagination,
        CountrySpecificOrderInputType $orderBy
    ): JsonResponse {
        if (!$request->headers->has('country')) {
            throw new BadRequestHttpException('Missing country header');
        }

        $countrySpecifics = $this->managerRegistry->getRepository(CountrySpecific::class)
            ->findByParams($request->headers->get('country'), $filter, $orderBy, $pagination);

        return $this->json($countrySpecifics);
    }

    #[Rest\Post('/web-app/v1/country-specifics')]
    public function create(CountrySpecificCreateInputType $inputType): JsonResponse
    {
        try {
            $countrySpecific = $this->countrySpecificService->create($inputType);
        } catch (UniqueConstraintViolationException) {
            throw new BadRequestHttpException(
                "Country specific option with the same name already exists, please choose another name."
            );
        }

        return $this->json($countrySpecific);
    }

    #[Rest\Put('/web-app/v1/country-specifics/{id}')]
    public function update(CountrySpecific $countrySpecific, CountrySpecificUpdateInputType $inputType): JsonResponse
    {
        try {
            $countrySpecific = $this->countrySpecificService->update($countrySpecific, $inputType);
        } catch (UniqueConstraintViolationException) {
            throw new BadRequestHttpException(
                "Country specific option with the same name already exists, please choose another name."
            );
        }

        return $this->json($countrySpecific);
    }

    #[Rest\Delete('/web-app/v1/country-specifics/{id}')]
    public function delete(CountrySpecific $object): JsonResponse
    {
        $this->countrySpecificService->delete($object);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
