<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use NewApiBundle\Entity\Organization;
use NewApiBundle\Entity\OrganizationServices;
use CommonBundle\Repository\OrganizationRepository;
use CommonBundle\Repository\OrganizationServicesRepository;
use CommonBundle\Utils\OrganizationService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\File\UploadService;
use NewApiBundle\InputType\OrganizationUpdateInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OrganizationController extends AbstractController
{
    /** @var UploadService */
    private $uploadService;

    /**
     * @var string[]
     */
    private $allowedMimeTypes;

    public function __construct(UploadService $uploadService, array $allowedMimeTypes)
    {
        $this->uploadService = $uploadService;
        $this->allowedMimeTypes = $allowedMimeTypes;
    }

    /**
     * @Rest\Get("/web-app/v1/organizations/{id}")
     *
     * @param Organization $organization
     *
     * @return JsonResponse
     */
    public function item(Organization $organization): JsonResponse
    {
        return $this->json($organization);
    }

    /**
     * @Rest\Put("/web-app/v1/organizations/{id}")
     *
     * @param Organization                $organization
     * @param OrganizationUpdateInputType $inputType
     * @param OrganizationService         $organizationService
     *
     * @return JsonResponse
     */
    public function update(Organization $organization, OrganizationUpdateInputType $inputType, OrganizationService $organizationService): JsonResponse
    {
        $organizationService->update($organization, $inputType);

        return $this->json($organization);
    }

    /**
     * @Rest\Get("/web-app/v1/organizations")
     *
     * @param Pagination             $pagination
     * @param OrganizationRepository $organizationRepository
     *
     * @return JsonResponse
     */
    public function list(Pagination $pagination, OrganizationRepository $organizationRepository): JsonResponse
    {
        $organizations = $organizationRepository->findByParams($pagination);

        return $this->json($organizations);
    }

    /**
     * @Rest\Get("/web-app/v1/organizations/{id}/services")
     *
     * @param Organization                   $organization
     * @param Pagination                     $pagination
     * @param OrganizationServicesRepository $organizationServicesRepository
     *
     * @return JsonResponse
     */
    public function listServices(
        Organization                   $organization,
        Pagination                     $pagination,
        OrganizationServicesRepository $organizationServicesRepository
    ): JsonResponse {
        $organizationServices = $organizationServicesRepository->findByOrganization($organization, $pagination);

        return $this->json($organizationServices);
    }

    /**
     * @Rest\Patch("/web-app/v1/organizations/services/{id}")
     *
     * @param Request              $request
     * @param OrganizationServices $organizationServices
     * @param OrganizationService  $organizationService
     *
     * @return JsonResponse
     */
    public function updateService(Request $request, OrganizationServices $organizationServices, OrganizationService $organizationService): JsonResponse
    {
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
     * @param Organization           $organization
     * @param Request                $request
     * @param OrganizationRepository $organizationRepository
     *
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function uploadImage(Organization $organization, Request $request, OrganizationRepository $organizationRepository): JsonResponse
    {
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
