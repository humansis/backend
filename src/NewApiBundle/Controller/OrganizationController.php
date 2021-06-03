<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Entity\Organization;
use CommonBundle\Entity\OrganizationServices;
use CommonBundle\Repository\OrganizationRepository;
use CommonBundle\Repository\OrganizationServicesRepository;
use CommonBundle\Utils\OrganizationService;
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

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
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
     *
     * @return JsonResponse
     */
    public function update(Organization $organization, OrganizationUpdateInputType $inputType): JsonResponse
    {
        $this->get('organization_service')->update($organization, $inputType);

        return $this->json($organization);
    }

    /**
     * @Rest\Get("/web-app/v1/organizations")
     *
     * @param Pagination $pagination
     *
     * @return JsonResponse
     */
    public function list(Pagination $pagination): JsonResponse
    {
        /** @var OrganizationRepository $organizationRepository */
        $organizationRepository = $this->getDoctrine()->getRepository(Organization::class);

        $organizations = $organizationRepository->findByParams($pagination);

        return $this->json($organizations);
    }

    /**
     * @Rest\Get("/web-app/v1/organizations/{id}/services")
     *
     * @param Organization $organization
     * @param Pagination   $pagination
     *
     * @return JsonResponse
     */
    public function listServices(Organization $organization, Pagination $pagination): JsonResponse
    {
        /** @var OrganizationServicesRepository $organizationRepository */
        $organizationRepository = $this->getDoctrine()->getRepository(OrganizationServices::class);

        $organizationServices = $organizationRepository->findByOrganization($organization, $pagination);

        return $this->json($organizationServices);
    }

    /**
     * @Rest\Patch("/web-app/v1/organizations/services/{id}")
     *
     * @param Request              $request
     * @param OrganizationServices $organizationServices
     *
     * @return JsonResponse
     */
    public function updateService(Request $request, OrganizationServices $organizationServices): JsonResponse
    {
        /** @var OrganizationService $organizationService */
        $organizationService = $this->get('organization_service');

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
     * @param Organization $organization
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function uploadImage(Organization $organization, Request $request): JsonResponse
    {
        if (!($file = $request->files->get('file'))) {
            throw new BadRequestHttpException('File missing.');
        }

        if (!in_array($file->getMimeType(), ['image/gif', 'image/jpeg', 'image/png'])) {
            throw new BadRequestHttpException('Invalid file type.');
        }

        $url = $this->uploadService->upload($file, 'organization');

        $organization->setLogo($url);

        $this->getDoctrine()->getManager()->persist($organization);
        $this->getDoctrine()->getManager()->flush();

        return $this->json(['url' => $url]);
    }

}
