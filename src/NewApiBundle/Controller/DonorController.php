<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\File\UploadService;
use NewApiBundle\InputType\DonorCreateInputType;
use NewApiBundle\InputType\DonorFilterInputType;
use NewApiBundle\InputType\DonorOrderInputType;
use NewApiBundle\InputType\DonorUpdateInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Donor;
use ProjectBundle\Utils\DonorService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DonorController extends AbstractController
{
    /** @var DonorService */
    private $donorService;

    /** @var UploadService */
    private $uploadService;

    /**
     * DonorController constructor.
     *
     * @param DonorService $donorService
     */
    public function __construct(DonorService $donorService, UploadService $uploadService)
    {
        $this->donorService = $donorService;
        $this->uploadService = $uploadService;
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

    /**
     * @Rest\Post("/donors/{id}/images")
     *
     * @param Donor   $donor
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function uploadImage(Donor $donor, Request $request): JsonResponse
    {
        if (!($file = $request->files->get('file'))) {
            throw new BadRequestHttpException('File missing.');
        }

        if (!in_array($file->getMimeType(), ['image/gif', 'image/jpeg', 'image/png'])) {
            throw new BadRequestHttpException('Invalid file type.');
        }

        $url = $this->uploadService->upload($file, 'donors');

        $donor->setLogo($url);

        $this->getDoctrine()->getManager()->persist($donor);
        $this->getDoctrine()->getManager()->flush();

        return $this->json(['url' => $url]);
    }
}
