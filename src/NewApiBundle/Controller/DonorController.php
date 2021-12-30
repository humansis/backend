<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Controller\ExportController;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\File\UploadService;
use NewApiBundle\InputType\DonorCreateInputType;
use NewApiBundle\InputType\DonorFilterInputType;
use NewApiBundle\InputType\DonorOrderInputType;
use NewApiBundle\InputType\DonorUpdateInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Donor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DonorController extends AbstractController
{
    /** @var UploadService */
    private $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    /**
     * @Rest\Get("/web-app/v1/donors/exports")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function exports(Request $request): BinaryFileResponse
    {
        $request->query->add(['donors' => true]);

        return $this->forward(ExportController::class.'::exportAction', [], $request->query->all());
    }

    /**
     * @Rest\Get("/web-app/v1/donors/{id}")
     * @Cache(lastModified="donor.getLastModifiedAt()", public=true)
     *
     * @param Donor $donor
     *
     * @return JsonResponse
     */
    public function item(Donor $donor): JsonResponse
    {
        return $this->json($donor);
    }

    /**
     * @Rest\Get("/web-app/v1/donors")
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
     * @Rest\Post("/web-app/v1/donors")
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
     * @Rest\Put("/web-app/v1/donors/{id}")
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
     * @Rest\Delete("/web-app/v1/donors/{id}")
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

    /**
     * @Rest\Post("/web-app/v1/donors/{id}/images")
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
