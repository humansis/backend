<?php

declare(strict_types=1);

namespace Controller;

use Doctrine\ORM\Exception\ORMException;
use Entity\Organization;
use Entity\OrganizationServices;
use Repository\OrganizationRepository;
use Repository\OrganizationServicesRepository;
use Utils\OrganizationService;
use Doctrine\ORM\OptimisticLockException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\File\UploadService;
use InputType\OrganizationUpdateInputType;
use Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OrganizationController extends AbstractController
{
    /**
     * @param string[] $allowedMimeTypes
     */
    public function __construct(private readonly UploadService $uploadService, private readonly array $allowedMimeTypes)
    {
    }

    /**
     * @Rest\Get("/web-app/v1/organizations/{id}")
     *
     *
     */
    public function item(Organization $organization): JsonResponse
    {
        return $this->json($organization);
    }

    /**
     * @Rest\Put("/web-app/v1/organizations/{id}")
     *
     *
     */
    public function update(
        Organization $organization,
        OrganizationUpdateInputType $inputType,
        OrganizationService $organizationService
    ): JsonResponse {
        $organizationService->update($organization, $inputType);

        return $this->json($organization);
    }

    /**
     * @Rest\Get("/web-app/v1/organizations")
     *
     *
     */
    public function list(Pagination $pagination, OrganizationRepository $organizationRepository): JsonResponse
    {
        $organizations = $organizationRepository->findByParams($pagination);

        return $this->json($organizations);
    }

    /**
     * @Rest\Get("/web-app/v1/organizations/{id}/services")
     *
     *
     */
    public function listServices(
        Organization $organization,
        Pagination $pagination,
        OrganizationServicesRepository $organizationServicesRepository
    ): JsonResponse {
        $organizationServices = $organizationServicesRepository->findByOrganization($organization, $pagination);

        return $this->json($organizationServices);
    }

    /**
     * @Rest\Patch("/web-app/v1/organizations/services/{id}")
     *
     *
     */
    public function updateService(
        Request $request,
        OrganizationServices $organizationServices,
        OrganizationService $organizationService
    ): JsonResponse {
        if ($request->request->has('enabled')) {
            $organizationService->setEnable($organizationServices, $request->request->getBoolean('enabled'));
        }

        if ($request->request->has('parameters')) {
            $organizationService->setParameters($organizationServices, $request->request->get('parameters'));
        }

        return $this->json($organizationServices);
    }

    /**
     * @Rest\Post("/web-app/v1/organizations/{id}/images")
     *
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function uploadImage(
        Organization $organization,
        Request $request,
        OrganizationRepository $organizationRepository
    ): JsonResponse {
        if (!($file = $request->files->get('file'))) {
            throw new BadRequestHttpException('File missing.');
        }

        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            throw new BadRequestHttpException('Invalid file type.');
        }

        $url = $this->uploadService->upload($file, 'organization');

        $organization->setLogo($url);
        $organizationRepository->save($organization);

        return $this->json(['url' => $url]);
    }
}
